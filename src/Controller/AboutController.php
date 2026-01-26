<?php

namespace App\Controller;

use App\Repository\AboutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about')]
    public function index(AboutRepository $aboutRepository): Response
    {
        $trainer = $aboutRepository->find(1);

        return $this->render('about/index.html.twig', [
            'trainer' => $trainer
        ]);
    }
}
