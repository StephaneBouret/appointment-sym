<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Form\AppointmentEditPersonsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentPersonsController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em,) {}

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/personnes/edit', name: 'app_appointment_edit_persons', methods: ['GET', 'POST'])]
    public function editPersons(Appointment $appointment, Request $request): Response
    {
        $user = $this->getUser();

        if (
            !$appointment ||
            $appointment->getUser() !== $user ||
            $appointment->getStatus() === AppointmentStatus::CANCELED
        ) {
            $this->addFlash('danger', 'Action non autorisée.');
            return $this->redirectToRoute('app_appointment_list');
        }

        $type = $appointment->getType();
        $isCouple = $type && method_exists($type, 'isCouple') ? $type->isCouple() : ((int)$type->getParticipants() === 2);

        $form = $this->createForm(AppointmentEditPersonsType::class, $appointment, [
            'is_couple' => $isCouple,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appointment->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();

            $this->addFlash('success', 'Informations mises à jour.');
            return $this->redirectToRoute('app_appointment_list');
        }

        return $this->render('appointment/edit_persons.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }
}
