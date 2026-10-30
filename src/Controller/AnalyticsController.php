<?php

namespace App\Controller;

use App\Analytics\AnalyticsConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnalyticsController extends AbstractController
{
    #[Route('/audience/frame', name: 'app_analytics_frame', methods: ['GET'])]
    public function frame(Request $request, AnalyticsConfig $analytics): Response
    {
        if (!$analytics->isEnabled() || $request->getQueryString() !== null) {
            return new Response('', 404, ['Cache-Control' => 'no-store']);
        }
        return $this->render('analytics/frame.html.twig', ['analyticsConfig' => $analytics->clientConfig()], new Response('', 200, [
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'; base-uri 'none'; form-action 'none'",
        ]));
    }
}
