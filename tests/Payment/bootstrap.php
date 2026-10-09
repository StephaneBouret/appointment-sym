<?php

// Deliberately never load .env or boot the application's kernel.
require dirname(__DIR__, 2).'/vendor/autoload.php';
date_default_timezone_set('UTC');
\Doctrine\DBAL\Types\Type::addType(\App\Doctrine\Type\UtcDateTimeImmutableType::NAME, \App\Doctrine\Type\UtcDateTimeImmutableType::class);

// Fail closed if any test accidentally reaches the real Stripe gateway.
\Stripe\ApiRequestor::setHttpClient(new class implements \Stripe\HttpClient\ClientInterface {
    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        throw new \LogicException('External Stripe HTTP is forbidden in payment tests.');
    }
});
