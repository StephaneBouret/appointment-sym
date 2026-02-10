<?php

namespace App\Controller\Appointment;

use App\Entity\AppointmentType;
use App\Repository\AppointmentTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('rendez-vous/types')]
final class AppointmentTypeController extends AbstractController
{
    #[Route('', name: 'app_appointment_types', methods: ['GET'])]
    public function index(Request $request, AppointmentTypeRepository $repo): Response
    {
        $types = $repo->findAll();

        if ($request->isXmlHttpRequest()) {
            return $this->render('appointment/_types.html.twig', [
                'types' => $types
            ]);
        }

        return $this->render('appointment/types.html.twig', [
            'types' => $types,
        ]);
    }

    #[Route('/{id}', name: 'app_appointment_type_detail', methods: ['GET'])]
    public function detail(AppointmentType $type, Request $request): Response
    {
        return $this->render('appointment/type_detail.html.twig', [
            'type' => $type,
        ]);
    }
}
