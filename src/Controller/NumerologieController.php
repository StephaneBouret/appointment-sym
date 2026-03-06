<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NumerologieController extends AbstractController
{
    #[Route('/numerologie', name: 'app_numerologie')]
    public function index(): Response
    {
        $cities = [
            [
                'name' => 'La Plaine-sur-Mer',
                'route' => 'seo_local_page',
                'params' => ['slug' => 'numerologie-la-plaine-sur-mer'],
            ],
            [
                'name' => 'Pornic',
                'route' => 'seo_local_page',
                'params' => ['slug' => 'numerologue-pornic'],
            ],
            [
                'name' => 'Saint-Brévin-les-Pins',
                'route' => 'seo_local_page',
                'params' => ['slug' => 'numerologue-saint-brevin'],
            ],
            [
                'name' => 'Saint-Nazaire',
                'route' => 'seo_local_page',
                'params' => ['slug' => 'numerologue-saint-nazaire'],
            ],
            [
                'name' => 'Nantes',
                'route' => 'seo_local_page',
                'params' => ['slug' => 'numerologue-nantes'],
            ],
        ];

        return $this->render('numerology/index.html.twig', [
            'cities' => $cities,
        ]);
    }
}
