<?php

namespace App\Service;

use App\Entity\AppointmentType;
use App\Enum\AppointmentStatus;
use App\Service\ScheduleSettingService;
use App\Repository\AppointmentRepository;
use App\Repository\UnavailabilityRepository;
use App\Repository\UnavailableDayRepository;

/**
 * Génère les créneaux disponibles pour un type de RDV et une date donnée.
 * - Respecte les jours fermés (open_days), les plages horaires matin/après-midi,
 * - Exclut les jours off (admin) et les indispos horaires,
 * - Applique le mode "créneaux fixes" (cadence = durée + buffer) ou un pas libre,
 * - Exclut les RDV PENDING/CONFIRMED existants en leur appliquant un buffer (avant/après),
 * - 🚧 Barrière ouvrable (opening_delay_hours en jours OUVRABLES) pour éviter toute fuite.
 */
final class SlotService
{
    public function __construct(
        private AppointmentRepository $appointmentRepo,
        private UnavailableDayRepository $unavailableDayRepo,
        private UnavailabilityRepository $unavailabilityRepo,
        private ScheduleSettingService $settings,
    ) {}

    /**
     * Retourne les créneaux libres (start/end en \DateTimeImmutable) pour un type et un jour donné
     *
     * @param AppointmentType $type
     * @param \DateTimeInterface $date (partie "date" utilisée, TZ Europe/Paris recommandé)
     * @return array<int, array{start:\DateTimeImmutable,end:\DateTimeImmutable}>
     */
    public function getAvailableSlots(AppointmentType $type, \DateTimeInterface $date): array
    {
        $tzParis = new \DateTimeZone('Europe/Paris');

        // 0) Barrière ouvrable (J+ceil(H/24) ouvrables) — garde-fou côté service
        $barrier = $this->computeBusinessBarrier($tzParis);
        $dayParis = \DateTimeImmutable::createFromFormat('!Y-m-d', $date->format('Y-m-d'), $tzParis);
        if (!$dayParis) {
            return [];
        }
        if ($dayParis < $barrier) {
            return []; // sécurité supplémentaire : aucune fuite avant la barrière
        }

        // 1) Jour bloqué par l'admin ? => aucun slot
        if ($this->unavailableDayRepo->isUnavailable($dayParis)) {
            return [];
        }

        // 2) Jour bloqué via indispo "all day" ?
        if ($this->unavailabilityRepo->hasAllDay($dayParis)) {
            return [];
        }

        // 3) Filtrage par jours ouverts (open_days: ex "1,2,3,4,5"; 1 = lundi)
        $openDays  = $this->settings->getCsvIntList('open_days', '1,2,3,4,5');
        $dayOfWeek = (int) $dayParis->format('N'); // 1..7
        if (!in_array($dayOfWeek, $openDays, true)) {
            return []; // jour fermé
        }

        // 4) Créneaux d'ouverture
        $morningStart    = (string) ($this->settings->get('morning_start', '09:00') ?: '09:00');
        $morningEnd      = (string) ($this->settings->get('morning_end',   '12:00') ?: '12:00');
        $afternoonStart  = (string) ($this->settings->get('afternoon_start', '14:00') ?: '14:00');
        $afternoonEnd    = (string) ($this->settings->get('afternoon_end',   '18:00') ?: '18:00');

        // 5) Données du jour (RDV confirmés + indispos horaires)
        $appointments = $this->appointmentRepo->findByDate($dayParis); // idéalement: CONFIRMED/PENDING
        // Par sûreté, on filtre ici
        $appointments = array_filter($appointments, function ($a) {
            if (!method_exists($a, 'getStatus')) return true;
            $st = $a->getStatus();
            return in_array($st, [AppointmentStatus::PENDING, AppointmentStatus::CONFIRMED], true);
        });
        $unavails = $this->unavailabilityRepo->findForDate($dayParis);

        // 6) Génération des slots (matin + après-midi)
        $slots = [];
        $slots = array_merge($slots, $this->generateSlots($type, $dayParis, $morningStart,   $morningEnd,   $appointments, $unavails));
        $slots = array_merge($slots, $this->generateSlots($type, $dayParis, $afternoonStart, $afternoonEnd, $appointments, $unavails));

        // (optionnel) filtre final si qqch dépasse la barrière par erreur
        return array_values(array_filter($slots, fn($s) => $s['start'] >= $barrier));
    }

    /**
     * Génère les créneaux pour une plage (ex: 09:00-12:00) en évitant les chevauchements
     *
     * @param AppointmentType $type
     * @param \DateTimeImmutable $date (seule la date est utilisée, Europe/Paris)
     * @param string $start "HH:MM"
     * @param string $end   "HH:MM"
     * @param array<int,object>  $appointments objets avec getStartAt()/getEndAt()
     * @param array<int,object>  $unavails     objets avec getStartAt()/getEndAt() (même jour)
     * @return array<int, array{start:\DateTimeImmutable,end:\DateTimeImmutable}>
     */
    private function generateSlots(
        AppointmentType $type,
        \DateTimeImmutable $date,
        string $start,
        string $end,
        array $appointments,
        array $unavails
    ): array {
        $duration = (int) $type->getDuration(); // minutes
        if ($duration <= 0) {
            return [];
        }

        // Lecture des réglages
        $buffer = max(0, $this->settings->getInt('slot_buffer_minutes', 0));
        $fixed  = $this->settings->getBool('fixed_slots', true); // ON par défaut
        $step   = $fixed ? ($duration + $buffer) : max(5, $this->settings->getInt('slot_step_minutes', 15));

        // Construction avec TZ explicite (Europe/Paris)
        [$hStart, $mStart] = explode(':', $start);
        [$hEnd,   $mEnd]   = explode(':', $end);
        $tz = new \DateTimeZone('Europe/Paris');
        $windowStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . " $hStart:$mStart", $tz);
        $windowEnd   = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . " $hEnd:$mEnd",   $tz);

        if (!$windowStart || !$windowEnd || $windowEnd <= $windowStart) {
            return [];
        }

        $slots = [];
        $stepInterval = new \DateInterval('PT' . $step . 'M');

        for ($cursor = $windowStart; ($cursor->getTimestamp() + $duration * 60) <= $windowEnd->getTimestamp(); $cursor = $cursor->add($stepInterval)) {
            $slotEnd = $cursor->modify("+{$duration} minutes");

            // 1) Indispos horaires (sans buffer)
            if ($this->overlapAny($cursor, $slotEnd, $unavails, 0)) {
                continue;
            }

            // 2) RDV confirmés/PENDING (élargis du buffer avant/après)
            if ($this->overlapAny($cursor, $slotEnd, $appointments, $buffer)) {
                continue;
            }

            $slots[] = ['start' => $cursor, 'end' => $slotEnd];
        }

        return $slots;
    }

    private function overlapAny(\DateTimeImmutable $start, \DateTimeImmutable $end, iterable $periods, int $buffer): bool
    {
        foreach ($periods as $p) {
            $pStart = $p->getStartAt();
            $pEnd   = $p->getEndAt();
            if (!$pStart || !$pEnd) {
                continue;
            }

            if ($buffer > 0) {
                $pStart = $pStart->modify("-{$buffer} minutes");
                $pEnd   = $pEnd->modify("+{$buffer} minutes");
            }

            if ($pStart < $end && $pEnd > $start) {
                return true;
            }
        }
        return false;
    }

    /**
     * Barrière ouvrable : aujourd'hui 00:00 (Europe/Paris) + ceil(H/24) JOURS OUVRABLES,
     * et on commence à exposer APRÈS ces jours (lendemain).
     */
    private function computeBusinessBarrier(\DateTimeZone $tzParis): \DateTimeImmutable
    {
        $openingDelayHours = max(0, $this->settings->getInt('opening_delay_hours', 48));
        $daysToAdd = (int) ceil($openingDelayHours / 24);
        $openDays  = $this->settings->getCsvIntList('open_days', '1,2,3,4,5'); // 1=lun..7=dim

        $d = (new \DateTimeImmutable('now', $tzParis))->setTime(0, 0);
        $remaining = $daysToAdd;

        // 🟢 On compte aussi "aujourd'hui" si ouvré
        while ($remaining > 0) {
            $dow = (int) $d->format('N'); // 1..7
            if (in_array($dow, $openDays, true)) {
                $remaining--;
                if ($remaining === 0) {
                    break; // on s'arrête sur le Nᵉ jour ouvré
                }
            }
            $d = $d->modify('+1 day');
        }

        // Ouverture = lendemain du Nᵉ jour ouvré consommé
        return $d->modify('+1 day');
    }
}
