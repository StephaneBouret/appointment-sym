<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Stripe\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentPaymentController extends AbstractController
{
    public function __construct(protected StripeService $stripeService) {}

    #[IsGranted('ROLE_USER')]
    #[Route('/pay/{id}', name: 'appointment_payment_form')]
    public function showCardForm(Appointment $appointment)
    {
        $user = $this->getUser();

        if (
            !$appointment ||
            $appointment->getUser() !== $user ||
            $appointment->getStatus() === AppointmentStatus::CONFIRMED
        ) {
            return $this->redirectToRoute('app_appointment_list');
        }

        $paymentIntent = $this->stripeService->getPaymentIntentForAppointment($appointment);

        return $this->render('appointment/payment.html.twig', [
            'clientSecret' => $paymentIntent->client_secret,
            'appointment' => $appointment,
            'stripePublicKey' => $this->stripeService->getPublicKey(),
        ]);
    }
}
