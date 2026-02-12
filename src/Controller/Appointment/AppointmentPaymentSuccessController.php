<?php

namespace App\Controller\Appointment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Event\AppointmentSuccessEvent;
use App\Service\PurchaseNumberGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
final class AppointmentPaymentSuccessController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $em, protected PurchaseNumberGenerator $generator) {}

    #[IsGranted('ROLE_USER')]
    #[Route('/terminate/{id}', name: 'app_appointment_payment_success')]
    public function success(Appointment $appointment, EventDispatcherInterface $dispatcher)
    {
        $user = $this->getUser();

        if (
            !$appointment ||
            $appointment->getUser() !== $user ||
            $appointment->getStatus() === AppointmentStatus::CONFIRMED
        ) {
            return $this->redirectToRoute('app_appointment_list');
        }

        $appointment->setStatus(AppointmentStatus::CONFIRMED)
            ->setNumber($this->generator->generate());
        $this->em->flush();

        // Dispatch de l'événement "Succès RDV"
        $dispatcher->dispatch(new AppointmentSuccessEvent($appointment), AppointmentSuccessEvent::NAME);

        $this->addFlash('success', 'La commande a été payée et confirmée !');

        return $this->redirectToRoute('app_appointment_list');
    }
}
