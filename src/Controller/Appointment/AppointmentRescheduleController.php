<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Event\AppointmentRescheduledEvent;
use App\Repository\AppointmentRepository;
use App\Service\ScheduleSettingService;
use App\Service\SlotService;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentRescheduleController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[IsGranted('ROLE_USER')]
    #[Route('/reschedule', name: 'app_appointment_reschedule', methods: ['POST'])]
    public function __invoke(
        Request $request,
        AppointmentRepository $repo,
        SlotService $slotService,
        ScheduleSettingService $settings,
        EventDispatcherInterface $dispatcher
    ): JsonResponse {
        $data = json_decode($request->getContent() ?: '[]', true);
        $id = (int) ($data['id'] ?? 0);
        $startIsoTz = (string) ($data['startIsoTz'] ?? '');
        $csrf = $request->headers->get('X-CSRF-TOKEN', '');

        if ($id <= 0 || $startIsoTz === '') {
            return $this->json(['error' => 'Paramètres manquants'], 422);
        }
        if (!$this->isCsrfTokenValid('reschedule_appointment_' . $id, $csrf)) {
            return $this->json(['error' => 'CSRF invalide'], 400);
        }

        /** @var Appointment|null $a */
        $a = $repo->find($id);
        $user = $this->getUser();
        if (!$a || $a->getUser() !== $user) {
            return $this->json(['error' => 'Rendez-vous introuvable'], 404);
        }
        if ($a->getStatus() === AppointmentStatus::CANCELED) {
            return $this->json(['error' => 'Rendez-vous annulé'], 400);
        }
        $type = $a->getType();
        if (!$type) {
            return $this->json(['error' => 'Type manquant'], 400);
        }

        // Start choisi: ISO avec fuseau → on force Europe/Paris côté serveur
        $tz = new DateTimeZone('Europe/Paris');
        $nowParis = new DateTimeImmutable('now', $tz);

        // Règle : on ne replanifie pas un RDV passé
        if ($a->getStartAt() && $a->getStartAt() <= $nowParis) {
            return $this->json(['error' => 'Ce rendez-vous est passé et ne peut plus être replanifié.'], 422);
        }

        // Parsing Date
        try {
            $start = new DateTimeImmutable($startIsoTz);
            // Normalise en Europe/Paris (si besoin), puis conserve en immutable (UTC si c'est la convention)
            $startParis = $start->setTimezone($tz);
        } catch (\Throwable) {
            return $this->json(['error' => 'Date invalide'], 422);
        }

        // Préavis minimal (CGV)
        $minNotice = max(0, (int) $settings->getInt('reschedule_min_notice_hours', 24));
        $limit = $nowParis->modify("+{$minNotice} hours");

        $originalStartParis = $a->getStartAt()?->setTimezone($tz);
        if ($originalStartParis && $originalStartParis < $limit) {
            return $this->json([
                'error' => sprintf("Le report doit être demandé au moins %d h avant l'heure initiale du rendez-vous.", $minNotice),
            ], 422);
        }
        if ($startParis < $limit) {
            return $this->json([
                'error' => sprintf('Le report doit respecter une préavis de %d h.', $minNotice)
            ], 422);
        }

        // Validation: le slot doit exister ce jour-là pour ce type (avec barrières/buffers)
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $startParis->format('Y-m-d'), $tz);
        if (!$day) {
            return $this->json(['error' => 'Date invalide'], 422);
        }

        $available = $slotService->getAvailableSlots($type, $day); // applique déjà barrière + collisions
        $duration = (int) $type->getDuration();
        if ($duration < 0) {
            return $this->json(['error' => 'Durée de type invalide'], 400);
        }
        $endParis = $startParis->modify("+{$duration} minutes");

        $match = false;
        foreach ($available as $s) {
            if (
                $s['start']->getTimestamp() === $startParis->getTimestamp()
                && $s['end']->getTimestamp() === $endParis->getTimestamp()
            ) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            return $this->json(['error' => 'Créneau indisponible'], 409);
        }

        // Persistance
        $oldStart = $a->getStartAt();
        $oldEnd = $a->getEndAt();

        // OK → enregistre le report (conserve status)
        $a->setStartAt($startParis);
        $a->setEndAt($endParis);
        $this->em->flush();

        $dispatcher->dispatch(new AppointmentRescheduledEvent($a, $oldStart, $oldEnd), AppointmentRescheduledEvent::NAME);

        return $this->json([
            'ok' => true,
            'start' => $a->getStartAt()?->format(\DateTimeInterface::RFC3339),
            'end'   => $a->getEndAt()?->format(\DateTimeInterface::RFC3339),
        ]);
    }
}
