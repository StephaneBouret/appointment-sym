<?php

namespace App\Controller;

use App\Google\GoogleService;
use App\Repository\AppointmentTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        GoogleService $googleService,
        AppointmentTypeRepository $appointmentTypes,
        ParameterBagInterface $parameters,
    ): Response
    {
        $configuredId = $parameters->has('app.home_featured_appointment_type_id')
            ? $parameters->get('app.home_featured_appointment_type_id')
            : null;
        $featuredId = filter_var($configuredId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $this->render('home/index.html.twig', [
            'googleReviews' => $googleService->getPlaceReviewsSummary(),
            'featuredAppointmentType' => $featuredId === false ? null : $appointmentTypes->find($featuredId),
        ]);
    }
}
