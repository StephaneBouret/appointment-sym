<?php

namespace App\Google;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleService
{
    public function __construct(
        private readonly string $googleKey,
        private readonly string $googlePlaceId,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {}

    public function getGoogleKey(): string
    {
        return $this->googleKey;
    }

    public function getPlaceReviewsSummary(): array
    {
        return $this->cache->get('google_reviews_summary_'.$this->googlePlaceId, function (ItemInterface $item): array {
            $url = sprintf(
                'https://places.googleapis.com/v1/places/%s',
                urlencode($this->googlePlaceId)
            );

            try {
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => [
                        'X-Goog-Api-Key' => $this->googleKey,
                        'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,googleMapsUri',
                    ],
                    'timeout' => 5,
                    'max_duration' => 5,
                ]);

                $data = $response->getStatusCode() >= 400 ? [] : $response->toArray(false);
            } catch (ExceptionInterface) {
                $data = [];
            }

            $summary = [
                'rating' => isset($data['rating']) ? (float) $data['rating'] : null,
                'reviewCount' => isset($data['userRatingCount']) ? (int) $data['userRatingCount'] : null,
                'googleMapsUri' => $data['googleMapsUri'] ?? null,
                'displayName' => $data['displayName']['text'] ?? null,
            ];

            $item->expiresAfter($summary['rating'] !== null && $summary['reviewCount'] !== null ? 86400 : 300);

            return $summary;
        });
    }
}
