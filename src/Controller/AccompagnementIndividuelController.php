<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccompagnementIndividuelController extends AbstractController
{
    #[Route('/accompagnement-individuel', name: 'app_accompagnement_individuel')]
    public function index(): Response
    {
        return $this->render('accompagnement/index.html.twig');
    }
}
