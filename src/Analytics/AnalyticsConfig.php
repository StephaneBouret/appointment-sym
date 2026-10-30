<?php

namespace App\Analytics;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Fail closed: only these route names and parameters can become analytics metadata. */
final class AnalyticsConfig
{
    public const ROUTES = [
        'app_home' => 'Accueil',
        'app_about' => 'À propos',
        'app_accompagnement_individuel' => 'Accompagnement individuel',
        'app_contact' => 'Contact',
        'app_legal' => 'Mentions légales',
        'app_privacy' => 'Politique de confidentialité',
        'app_terms' => 'Conditions générales de vente',
        'app_appointment_types' => 'Prestations',
    ];
    public const LOCAL_SLUGS = [
        'accompagnement-la-plaine-sur-mer', 'accompagnement-pornic',
        'accompagnement-saint-brevin', 'accompagnement-saint-nazaire', 'accompagnement-nantes',
    ];

    public function __construct(
        private readonly UrlGeneratorInterface $router,
        #[Autowire('%env(GA4_ENABLED)%')] private readonly string $enabled,
        #[Autowire('%env(GA4_MEASUREMENT_ID)%')] private readonly string $measurementId,
    ) {}

    public function isEnabled(): bool
    {
        return in_array(strtolower($this->enabled), ['1', 'true'], true)
            && preg_match('/^G-[A-Z0-9]{10}$/D', $this->measurementId) === 1;
    }

    public function page(?Request $request): ?array
    {
        if (!$request || !$request->isMethod('GET')) {
            return null;
        }
        $route = $request->attributes->get('_route');
        if (isset(self::ROUTES[$route])) {
            return ['path' => $this->router->generate($route), 'title' => self::ROUTES[$route]];
        }
        if ($route === 'seo_local_page' && in_array($request->attributes->get('slug'), self::LOCAL_SLUGS, true)) {
            return ['path' => $this->router->generate($route, ['slug' => $request->attributes->get('slug')]), 'title' => 'Accompagnement local'];
        }
        $id = (string) $request->attributes->get('id', '');
        if ($route === 'app_appointment_type_detail' && preg_match('/^[1-9][0-9]{0,9}$/D', $id)) {
            return ['path' => $this->router->generate($route, ['id' => $id]), 'title' => 'Fiche prestation'];
        }
        return null;
    }

    public function clientConfig(?Request $request = null): array
    {
        $pages = [];
        foreach (self::ROUTES as $route => $title) {
            $pages[$this->router->generate($route)] = $title;
        }
        foreach (self::LOCAL_SLUGS as $slug) {
            $pages[$this->router->generate('seo_local_page', ['slug' => $slug])] = 'Accompagnement local';
        }
        return [
            'enabled' => $this->isEnabled(),
            'id' => $this->isEnabled() ? $this->measurementId : '',
            'page' => $this->page($request),
            'pages' => $pages,
            'detailTemplate' => $this->router->generate('app_appointment_type_detail', ['id' => '9876543210']),
            'framePath' => $this->router->generate('app_analytics_frame'),
        ];
    }
}
