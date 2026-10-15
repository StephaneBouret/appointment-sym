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

        $response = $this->render($request->isXmlHttpRequest() ? 'appointment/_types.html.twig' : 'appointment/types.html.twig', [
            'types' => $types,
        ]);
        $response->setVary('X-Requested-With', false);

        return $response;
    }

    #[Route('/{id}', name: 'app_appointment_type_detail', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function detail(AppointmentType $type, Request $request): Response
    {
        $response = $this->render($request->isXmlHttpRequest() ? 'appointment/type_detail.html.twig' : 'appointment/type_page.html.twig', [
            'type' => $type,
        ]);
        $response->setVary('X-Requested-With', false);

        return $response;
    }
}
