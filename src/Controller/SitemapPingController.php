<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SitemapPingController extends AbstractController
{
    #[Route('/ping-google', name: 'ping_google')]
    public function ping(HttpClientInterface $client): Response
    {
        $sitemap = 'https://francetuncq.fr/sitemap.xml';

        $url = 'https://www.google.com/ping?sitemap=' . urlencode($sitemap);

        $response = $client->request('GET', $url);

        return new Response('Ping envoyé à Google : ' . $response->getStatusCode());
    }
}
