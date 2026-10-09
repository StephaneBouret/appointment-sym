<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Stripe\StripeService;
use App\Service\AppointmentPaymentService;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentPaymentController extends AbstractController
{
    public function __construct(protected StripeService $stripeService, private readonly AppointmentPaymentService $payments) {}

    #[IsGranted('ROLE_USER')]
    #[Route('/pay/{id}', name: 'appointment_payment_form', methods: ['GET'])]
    public function showCardForm(Appointment $appointment)
    {
        $user = $this->getUser();

        if (
            !$user ||
            $appointment->getUser() !== $user ||
            $appointment->getStatus() !== AppointmentStatus::PENDING
        ) {
            return $this->redirectToRoute('app_appointment_list');
        }

        if (!$appointment->getPayment()->acceptedAt) {
            return $this->redirectToRoute('app_appointment_checkout', ['id' => $appointment->getId()]);
        }
        try {
            $paymentIntent = $this->payments->getOrCreateIntent($appointment);
        } catch (ApiErrorException) {
            $this->addFlash('warning', 'Le service de paiement est temporairement indisponible. Réessayez depuis le récapitulatif.');
            return $this->redirectToRoute('app_appointment_checkout', ['id' => $appointment->getId()]);
        }
        if (!$paymentIntent || $paymentIntent->status === 'canceled') {
            $this->addFlash('warning', 'Ce paiement est expiré ou annulé. Merci de nous contacter.');
            return $this->redirectToRoute('app_appointment_list');
        }
        if ($paymentIntent->status === 'succeeded') {
            return $this->redirectToRoute('app_appointment_payment_success', ['id' => $appointment->getId()]);
        }

        return $this->render('appointment/payment.html.twig', [
            'clientSecret' => $paymentIntent->client_secret,
            'appointment' => $appointment,
            'stripePublicKey' => $this->stripeService->getPublicKey(),
        ]);
    }
}
