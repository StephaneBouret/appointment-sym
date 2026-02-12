<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Form\AppointmentCheckoutFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentCheckoutController extends AbstractController
{
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page')]
    #[Route('/checkout/{id}', name: 'app_appointment_checkout', methods: ['GET'])]
    public function index(Appointment $appointment): Response
    {
        $user = $this->getUser();

        // sécurité : propriétaire + statut valide
        if (!$user || $appointment->getUser() !== $user) {
            $this->addFlash('warning', 'Vous n\'êtes pas autorisé à accéder à ce rendez-vous');
            return $this->redirectToRoute('app_home');
        }

        if ($appointment->getStatus() === AppointmentStatus::CONFIRMED) {
            $this->addFlash('info', 'Ce rendez-vous est déjà confirmé.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(AppointmentCheckoutFormType::class);

        return $this->render('appointment/checkout.html.twig', [
            'user' => $user,
            'appointment' => $appointment,
            'type' => $appointment->getType(),
            'confirmationForm' => $form,
        ]);
    }
}
