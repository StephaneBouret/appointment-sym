<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocalSeoController extends AbstractController
{
    private const PAGES = [
        'numerologie-la-plaine-sur-mer' => [
            'city' => 'La Plaine-sur-Mer',
            'title' => 'Numérologie et coaching à la Plaine-sur-Mer',
            'meta' => 'Séances de numérologie à La Plaine-sur-Mer avec France Tuncq. Chemin de vie, nombres clés et accompagnement personnalisé.',
        ],
        'numerologue-pornic' => [
            'city' => 'Pornic',
            'title' => 'Numérologie et coaching à Pornic',
            'meta' => 'Séances de numérologie près de Pornic avec France Tuncq. Lecture personnalisée, chemin de vie et cycles.',
        ],
        'numerologue-saint-brevin' => [
            'city' => 'Saint-Brévin-les-Pins',
            'title' => 'Numérologie et coaching à Saint-Brévin-les-Pins',
            'meta' => 'Séances de numérologie près de Saint-Brévin-les-Pins avec France Tuncq. Chemin de vie, nombres clés, accompagnement.',
        ],
        'numerologue-saint-nazaire' => [
            'city' => 'Saint-Nazaire',
            'title' => 'Numérologie et coaching à Saint-Nazaire',
            'meta' => 'Séances de numérologie près de Saint-Nazaire avec France Tuncq. Chemin de vie, nombres clés, accompagnement.',
        ],
        'numerologue-nantes' => [
            'city' => 'Nantes',
            'title' => 'Numérologie et coaching à Nantes',
            'meta' => 'Séances de numérologie près de Nantes avec France Tuncq. Chemin de vie, nombres clés et rendez-vous en ligne.',
        ],
    ];
    #[Route('/{slug}', name: 'seo_local_page', requirements: ['slug' => 'numerologie-la-plaine-sur-mer|numerologue-pornic|numerologue-saint-brevin|numerologue-saint-nazaire|numerologue-nantes'])]
    public function local(string $slug): Response
    {
        $page = self::PAGES[$slug] ?? null;
        if (!$page) {
            throw $this->createNotFoundException();
        }
        return $this->render('seo/local.html.twig', [
            'slug' => $slug,
            'city' => $page['city'],
            'seoTitle' => $page['title'],
            'seoMeta' => $page['meta'],
        ]);
    }
}
