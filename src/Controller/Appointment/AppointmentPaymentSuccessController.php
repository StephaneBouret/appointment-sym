<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Service\AppointmentPaymentService;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentPaymentSuccessController extends AbstractController
{
    public function __construct(private readonly AppointmentPaymentService $payments) {}

    #[IsGranted('ROLE_USER')]
    #[Route('/terminate/{id}', name: 'app_appointment_payment_success', methods: ['GET'])]
    public function success(Appointment $appointment)
    {
        $user = $this->getUser();

        if (
            !$user || $appointment->getUser() !== $user
        ) {
            return $this->redirectToRoute('app_appointment_list');
        }

        try {
            $result = $this->payments->confirmReturn($appointment);
        } catch (ApiErrorException) {
            $result = 'unavailable';
        }
        if ($result === 'confirmed') {
            $this->addFlash('success', 'Le paiement a été vérifié et le rendez-vous est confirmé.');
        } elseif ($result === 'confirmed_then_canceled') {
            $this->addFlash('info', 'Ce paiement a déjà été pris en compte. Le rendez-vous a ensuite été annulé et reste annulé.');
        } elseif ($result === 'late_payment' || str_contains($result, 'mismatch') || $result === 'manual_confirmation_review') {
            $this->addFlash('warning', 'Ce paiement nécessite une vérification manuelle. Merci de nous contacter ; aucun nouveau créneau n’a été réservé.');
        } else {
            $this->addFlash('warning', 'Le paiement n’est pas encore vérifié. Le rendez-vous ne sera confirmé qu’après validation du paiement.');
        }

        return $this->redirectToRoute('app_appointment_list');
    }
}
