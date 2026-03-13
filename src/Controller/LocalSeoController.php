<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocalSeoController extends AbstractController
{
    private const PAGES = [
        'accompagnement-la-plaine-sur-mer' => [
            'city' => 'La Plaine-sur-Mer',
            'title' => 'Accompagnement individuel à La Plaine-sur-Mer',
            'meta' => 'Accompagnement individuel à La Plaine-sur-Mer avec France Tuncq : transition de vie, connaissance de soi, clarification personnelle et professionnelle.',
            'note' => 'Consultations proposées à La Plaine-sur-Mer et dans les communes voisines de la Côte de Jade.',
        ],
        'accompagnement-pornic' => [
            'city' => 'Pornic',
            'title' => 'Accompagnement individuel près de Pornic',
            'meta' => 'Accompagnement individuel près de Pornic avec France Tuncq : transition de vie, connaissance de soi et rendez-vous à distance ou en présentiel.',
            'note' => 'Cette page s\'adresse aux personnes situées à Pornic et dans les environs.',
        ],
        'accompagnement-saint-brevin' => [
            'city' => 'Saint-Brévin-les-Pins',
            'title' => 'Accompagnement individuel près de Saint-Brévin-les-Pins',
            'meta' => 'Accompagnement individuel près de Saint-Brévin-les-Pins avec France Tuncq : accompagnement personnel, transition de vie et recherche de sens.',
            'note' => 'Les séances peuvent intéresser les personnes de Saint-Brévin-les-Pins et de son secteur.',
        ],
        'accompagnement-saint-nazaire' => [
            'city' => 'Saint-Nazaire',
            'title' => 'Accompagnement individuel près de Saint-Nazaire',
            'meta' => 'Accompagnement individuel près de Saint-Nazaire avec France Tuncq : clarification, connaissance de soi et transitions personnelles ou professionnelles.',
            'note' => 'Une page dédiée aux personnes recherchant un accompagnement autour de Saint-Nazaire.',
        ],
        'accompagnement-nantes' => [
            'city' => 'Nantes',
            'title' => 'Accompagnement individuel près de Nantes',
            'meta' => 'Accompagnement individuel près de Nantes avec France Tuncq : transition de vie, connaissance de soi et rendez-vous en ligne.',
            'note' => 'Cette page est pensée pour les personnes de Nantes souhaitant bénéficier d\'un accompagnement individuel.',
        ],
    ];

    #[Route(
        '/{slug}',
        name: 'seo_local_page',
        requirements: ['slug' => 'accompagnement-la-plaine-sur-mer|accompagnement-pornic|accompagnement-saint-brevin|accompagnement-saint-nazaire|accompagnement-nantes']
    )]
    public function local(string $slug): Response
    {
        $page = self::PAGES[$slug] ?? null;

        if (!$page) {
            throw $this->createNotFoundException();
        }

        $relatedPages = [];

        foreach (self::PAGES as $pageSlug => $data) {
            if ($pageSlug === $slug) {
                continue;
            }

            $relatedPages[] = [
                'slug' => $pageSlug,
                'city' => $data['city'],
            ];
        }

        return $this->render('seo/local.html.twig', [
            'slug' => $slug,
            'city' => $page['city'],
            'seoTitle' => $page['title'],
            'seoMeta' => $page['meta'],
            'cityNote' => $page['note'],
            'relatedPages' => $relatedPages,
        ]);
    }
}
