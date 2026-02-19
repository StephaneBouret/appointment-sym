<?php

namespace App\EventListener;

use App\Service\SettingService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

#[AsEventListener(event: RequestEvent::class)]
final class MaintenanceListener
{
    /**
     * Préfixes d'URL autorisés même en maintenance
     * (admin, auth, 2fa, page maintenance elle-même, endpoints nécessaires).
     */
    private const BYPASS_PATH_PREFIXES = [
        '/admin',
        '/login',
        '/logout',
        '/2fa',
        '/2fa_check',
        '/contact',
        '/maintenance',
        '/api/launch-notification',
        '/_wdt',
        '/_profiler',
    ];

    public function __construct(
        private readonly SettingService $settings,
        private readonly Security $security,
        private readonly Environment $twig,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        // évite de gérer des sous-requêtes (assets, fragments, etc.)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $maintenance = $this->settings->isMaintenanceEnabled();

        if (!$maintenance) {
            return;
        }

        // on ne casse pas l'UX si on a des appels Ajax en front
        if ($request->isXmlHttpRequest()) {
            return;
        }

        $path = $request->getPathInfo();

        foreach (self::BYPASS_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        // les admins passent toujours
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $response = new Response(
            $this->twig->render('maintenance/index.html.twig'),
            Response::HTTP_SERVICE_UNAVAILABLE
        );

        // bonus propre pour crawlers/proxy
        $response->headers->set('Retry-After', '300');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        $event->setResponse($response);
        $event->stopPropagation();
    }
}
