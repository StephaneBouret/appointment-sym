<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Event\AppointmentCancelledEvent;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentCancelController extends AbstractController
{
    public function __construct(
        private AppointmentRepository $appointments,
        private EntityManagerInterface $em,
        private CsrfTokenManagerInterface $csrf,
        private EventDispatcherInterface $dispatcher,
    ) {}

    #[Route('/cancel', name: 'app_appointment_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page')]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) {
            return $this->json(['ok' => false, 'error' => 'ID manquant ou invalide'], 400);
        }

        // CSRF : token envoyé dans le header X-CSRF-TOKEN
        $csrfTokenHeader = $request->headers->get('X-CSRF-TOKEN', '');
        $csrfToken = new CsrfToken('cancel_appointment_' . $id, $csrfTokenHeader);
        if (!$this->csrf->isTokenValid($csrfToken)) {
            return $this->json(['ok' => false, 'error' => 'Jeton CSRF invalide'], 419);
        }

        /** @var Appointment|null $appointment */
        $appointment = $this->appointments->find($id);
        if (!$appointment) {
            return $this->json(['ok' => false, 'error' => 'Rendez-vous introuvable'], 404);
        }

        // Vérifie la propriété du RDV
        if ($appointment->getUser() !== $this->getUser()) {
            return $this->json(['ok' => false, 'error' => 'Accès non autorisé'], 403);
        }

        // Statut : on n'annule que si confirmé et non déjà annulé
        if ($appointment->getStatus() === AppointmentStatus::CANCELED) {
            return $this->json(['ok' => false, 'error' => 'Le rendez-vous est déjà annulé'], 409);
        }
        if ($appointment->getStatus() !== AppointmentStatus::CONFIRMED) {
            return $this->json(['ok' => false, 'error' => 'Le statut actuel ne permet pas l\'annulation'], 422);
        }

        // Dates en UTC
        $startAtUtc = $appointment->getStartAt();
        if (!$startAtUtc instanceof \DateTimeInterface) {
            return $this->json(['ok' => false, 'error' => 'Date de début absente ou invalide'], 500);
        }
        $nowUtc = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        // Politique CGV
        $policy = $this->computeRefundPolicy($nowUtc, \DateTimeImmutable::createFromInterface($startAtUtc));

        // Montant payé (en centimes) via le type
        $paidCents = null;
        if ($appointment->getType() && method_exists($appointment->getType(), 'getPrice')) {
            $paidCents = $appointment->getType()->getPrice(); // int (centimes)
        }

        $refundCents = null;
        if (is_int($paidCents)) {
            $refundCents = (int) round($paidCents * $policy['percent']);
        }

        // Mise à jour du RDV
        $appointment->setStatus(AppointmentStatus::CANCELED);
        $appointment->setUpdatedAt($nowUtc);

        $this->em->persist($appointment);
        $this->em->flush();

        $this->dispatcher->dispatch(new AppointmentCancelledEvent(
            appointment: $appointment,
            refundPercent: (int) round($policy['percent'] * 100),
            refundCents: $refundCents,
            policyTier: $policy['tier'],
            policyMessage: $policy['message']
        ), AppointmentCancelledEvent::NAME);

        // Ici, branchement futur : remboursement Stripe (total/partial) ou avoir interne
        // $this->refundService->refundAppointment($appointment, $refundCents);

        return $this->json([
            'ok' => true,
            'appointmentId' => $appointment->getId(),
            'newStatus' => $appointment->getStatus()->value,
            'refund' => [
                'percent' => (int) round($policy['percent'] * 100),
                'amount'  => $refundCents, // en centimes (null si inconnu)
                'tier'    => $policy['tier'], // gt48 / 48to24 / lt24
                'message' => $policy['message'],
            ],
        ]);
    }

    /**
     * CGV (en jours ouvrés):
     * - > 48h ouvrées avant → 100%
     * - 48h-24h ouvrées avant → 50%
     * - < 24h ouvrées ou le jour même → 0%
     */
    private function computeRefundPolicy(\DateTimeImmutable $nowUtc, \DateTimeImmutable $startAtUtc): array
    {
        $workingHours = $this->calculateWorkingHours($nowUtc, $startAtUtc);

        if ($workingHours > 48) {
            return [
                'tier' => 'gt48',
                'percent' => 1.0,
                'message' => 'Annulation effectuée plus de 48h avant : remboursement intégral (100%).',
            ];
        } elseif ($workingHours > 24) {
            return [
                'tier' => '48to24',
                'percent' => 0.5,
                'message' => 'Annulation effectuée entre 48h et 24h avant : remboursement à 50%.',
            ];
        }

        // Moins de 24h (ou passé)
        return [
            'tier' => 'lt24',
            'percent' => 0.0,
            'message' => 'Annulation effectuée moins de 24h avant (ou le jour même) : aucun remboursement.',
        ];
    }

    private function calculateWorkingHours(\DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        // Si la date de début est après la date de fin → aucun délai
        if ($from >= $to) {
            return 0.0;
        }

        $cursor = $from;
        $workingSeconds = 0;

        while ($cursor < $to) {
            // Si c'est un jour ouvré (Lun→ven)
            if ((int)$cursor->format('N') < 6) {
                // on ajoute 1 heure (3600 secondes)
                $workingSeconds += 3600;
            }
            $cursor = $cursor->modify('+1 hour');
        }

        return $workingSeconds / 3600.0;
    }
}
