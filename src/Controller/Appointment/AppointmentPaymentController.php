<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentPaymentController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/pay/{id}', name: 'appointment_payment_form')]
    public function showCardForm(Appointment $appointment)
    {
        $user = $this->getUser();
        dd($user);

        if (
            !$appointment ||
            $appointment->getUser() !== $user ||
            $appointment->getStatus() === AppointmentStatus::CONFIRMED
        ) {
            return $this->redirectToRoute('app_appointment_list');
        }

        // TODO PaymentIntent
    }
}
