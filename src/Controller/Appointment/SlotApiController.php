<?php

namespace App\Controller\Appointment;

use App\Repository\AppointmentTypeRepository;
use App\Service\ScheduleSettingService;
use App\Service\SlotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class SlotApiController extends AbstractController
{
    public function __construct(private readonly ScheduleSettingService $settingService) {}

    /**
     * GET /api/slots?type=ID&date=YYYY-MM-DD
     * -> renvoie les créneaux d'UN seul jour
     */
    #[Route('/slots', name: 'api_slots', methods: ['GET'])]
    public function slots(
        Request $request,
        SlotService $slots,
        AppointmentTypeRepository $appointmentTypeRepo
    ): JsonResponse {
        // 1) Inputs
        $typeId = (int) ($request->query->get('type') ?? 0);
        $date = (string) $request->query->get('date'); // attendu: YYYY-MM-DD
        if ($typeId <= 0 || $date === '') {
            return $this->json(['error' => 'Paramètres requis: type (int>0) et date (YYYY-MM-DD)'], 422);
        }

        $type = $appointmentTypeRepo->find($typeId);
        if (!$type) {
            return $this->json(['error' => 'Type introuvable'], 404);
        }

        // 2) Date stricte Europe/Paris
        $tz  = new \DateTimeZone('Europe/Paris');
        $barrier = $this->computeBarrier($tz);

        // Date demandée (00:00 Europe/Paris)
        $day = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, $tz);
        $errors = \DateTimeImmutable::getLastErrors() ?: ['warning_count' => 0, 'error_count' => 0];
        if (!$day || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
            return $this->json(['error' => 'Date invalide (format attendu: YYYY-MM-DD)'], 422);
        }

        // CLAMP serveur : si la date < barrière, on remonte à la barrière
        if ($day < $barrier) {
            $day = $barrier;
        }

        // Jours ouverts (1=lundi..7=dimanche) — default 1,2,3,4,5
        $openDays = $this->settingService->getCsvIntList('open_days', '1,2,3,4,5');
        if (!$this->isOpenDay($day, $openDays)) {
            return $this->json([]); // jour fermé → aucun créneau
        }

        // 3) Slots du jour (format events FullCalendar)
        try {
            $available = $slots->getAvailableSlots($type, $day);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }


        $now = new \DateTimeImmutable('now', $tz);
        $events = [];
        foreach ($available as $s) {
            // double filet : on coupe passé + toute fuite < barrière
            if ($s['end'] <= $now) {
                continue;
            }
            if ($s['start'] < $barrier) {
                continue;
            }
            $events[] = [
                'start' => $s['start']->format(\DateTimeInterface::RFC3339),
                'end'   => $s['end']->format(\DateTimeInterface::RFC3339),
            ];
        }

        $response = $this->json($events);
        $response->headers->set('Cache-Control', 'no-store');
        return $response;
    }

    /**
     * GET /api/fixed-slots (alias exact de /api/slots)
     */
    #[Route('/fixed-slots', name: 'api_fixed_slots', methods: ['GET'])]
    public function fixedSlots(
        Request $request,
        SlotService $slots,
        AppointmentTypeRepository $appointmentTypeRepo
    ): JsonResponse {
        // Alias: même logique que /slots
        return $this->slots($request, $slots, $appointmentTypeRepo);
    }


    /**
     * GET /api/fixed-slots-range?type=ID&start=YYYY-MM-DD&end=YYYY-MM-DD
     * -> renvoie les créneaux sur une fenêtre [start, end[ (end exclus)
     */
    #[Route('/fixed-slots-range', name: 'api_fixed_slots_range', methods: ['GET'])]
    public function fixedSlotsRange(
        Request $request,
        SlotService $slots,
        AppointmentTypeRepository $types
    ): JsonResponse {
        $typeId = (int) $request->query->get('type');
        $start  = (string) $request->query->get('start'); // YYYY-MM-DD
        $end    = (string) $request->query->get('end');   // YYYY-MM-DD (exclu)
        if ($typeId <= 0 || $start === '' || $end === '') {
            return $this->json(['error' => 'Paramètres requis: type, start, end (YYYY-MM-DD)'], 422);
        }

        $type = $types->find($typeId);
        if (!$type) return $this->json(['error' => 'Type introuvable'], 404);

        $tz = new \DateTimeZone('Europe/Paris');
        $barrier = $this->computeBarrier($tz);

        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', $start, $tz);
        $to   = \DateTimeImmutable::createFromFormat('!Y-m-d', $end, $tz);
        if (!$from || !$to || $to <= $from) {
            return $this->json(['error' => 'Période invalide'], 422);
        }

        // CLAMP serveur : si la fenêtre commence avant barrière, on remonte à barrière
        if ($from < $barrier) {
            $from = $barrier;
            if ($to <= $from) {
                // fenêtre vide -> renvoyer []
                return $this->json([]);
            }
        }

        // Jours ouverts (1=lundi..7=dimanche)
        $openDays = $this->settingService->getCsvIntList('open_days', '1,2,3,4,5');

        $now = new \DateTimeImmutable('now', $tz);
        $events = [];
        for ($d = $from; $d < $to; $d = $d->modify('+1 day')) {
            if (!$this->isOpenDay($d, $openDays)) {
                continue; // saute samedi/dimanche (ou selon config)
            }

            foreach ($slots->getAvailableSlots($type, $d) as $s) {
                // coupe le passé + toute fuite < barrière
                if ($s['end'] <= $now) continue;
                if ($s['start'] < $barrier) continue;

                $events[] = [
                    'start' => $s['start']->format(\DateTimeInterface::RFC3339),
                    'end'   => $s['end']->format(\DateTimeInterface::RFC3339),
                ];
            }
        }

        $response = $this->json($events);
        $response->headers->set('Cache-Control', 'public, max-age=30');
        return $response;
    }

    /**
     * Calcule la barrière d'ouverture en fonction du paramètre d'admin.
     * - Clé : 'opening_delay_hours' (défaut 48)
     * - Barrière = aujourd'hui 00:00 (Europe/Paris) + ceil(H/24) jours
     */
    private function computeBarrier(\DateTimeZone $tz): \DateTimeImmutable
    {
        $openingDelayHours = max(0, (int) $this->settingService->getInt('opening_delay_hours', 48));
        $daysToAdd = (int) ceil($openingDelayHours / 24);

        $openDays = $this->settingService->getCsvIntList('open_days', '1,2,3,4,5'); // 1=lun..7=dim

        $d = (new \DateTimeImmutable('now', $tz))->setTime(0, 0);
        $remaining = $daysToAdd;

        // 🟢 Inclure "aujourd'hui" si c'est un jour ouvré
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

    private function isOpenDay(\DateTimeInterface $date, array $openDays): bool
    {
        // N=1 (Lun) ... 7 (Dim)
        $dow = (int) $date->format('N');
        return in_array($dow, $openDays, true);
    }
}
