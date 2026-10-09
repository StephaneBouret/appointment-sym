<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Form\AppointmentCheckoutFormType;
use App\Service\AppointmentPaymentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentConfirmationController extends AbstractController
{
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page')]
    #[Route('/confirm/{id}', name: 'app_appointment_confirm', methods: ['POST'])]
    public function confirm(Appointment $appointment, Request $request, AppointmentPaymentService $payments): Response
    {
        $user = $this->getUser();

        if (!$user || $appointment->getUser() !== $user) {
            $this->addFlash('warning', 'Accès refusé à ce rendez-vous');
            return $this->redirectToRoute('app_home');
        }

        if ($appointment->getStatus() !== AppointmentStatus::PENDING) {
            $this->addFlash('info', 'Ce rendez-vous n’est plus en attente de paiement.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(AppointmentCheckoutFormType::class);
        $form->handleRequest($request);
        if (!$request->isMethod('POST') || !$form->isSubmitted() || !$form->isValid()) {
            return $this->render('appointment/checkout.html.twig', [
                'user' => $user, 'appointment' => $appointment,
                'type' => $appointment->getType(), 'confirmationForm' => $form,
            ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
        }
        if (!$payments->acceptTerms($appointment)) {
            $this->addFlash('warning', 'Ce rendez-vous ne peut plus être payé en ligne. Merci de nous contacter.');
            return $this->redirectToRoute('app_appointment_list');
        }

        return $this->redirectToRoute('appointment_payment_form', [
            'id' => $appointment->getId(),
        ]);
    }
}
