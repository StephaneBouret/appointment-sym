<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentConfirmationController extends AbstractController
{
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page')]
    #[Route('/confirm/{id}', name: 'app_appointment_confirm', methods: ['POST'])]
    public function confirm(Appointment $appointment): Response
    {
        $user = $this->getUser();

        if ($appointment->getUser() !== $user) {
            $this->addFlash('warning', 'Accès refusé à ce rendez-vous');
            return $this->redirectToRoute('app_home');
        }

        if ($appointment->getStatus() === AppointmentStatus::CONFIRMED) {
            $this->addFlash('info', 'Ce rendez-vous est déjà confirmé.');
            return $this->redirectToRoute('app_home');
        }

        return $this->redirectToRoute('appointment_payment_form', [
            'id' => $appointment->getId(),
        ]);
    }
}
