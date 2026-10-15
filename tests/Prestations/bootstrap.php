<?php

// No Dotenv: tests cannot inherit the development/production database or secrets.
require dirname(__DIR__, 2).'/vendor/autoload.php';
foreach ([
    'APP_ENV' => 'test', 'APP_DEBUG' => '0', 'APP_SECRET' => 'prestations-isolated-tests',
    'DATABASE_URL' => 'sqlite:///:memory:', 'TEST_TOKEN' => '',
    'MAILER_DSN' => 'null://null', 'MAILER_DEFAULT_FROM' => 'sender@example.test',
    'MESSENGER_TRANSPORT_DSN' => 'in-memory://',
    'DEFAULT_URI' => 'http://localhost', 'APP_HOSTNAME' => 'http://localhost',
    'GOOGLE_API_KEY' => '', 'GOOGLE_PLACE_ID' => 'unused',
    'STRIPE_SECRET_KEY' => 'sk_test_unused', 'STRIPE_PUBLIC_KEY' => 'pk_test_unused',
    'STRIPE_WEBHOOK_SECRET' => 'unused',
] as $key => $value) {
    $_SERVER[$key] = $_ENV[$key] = $value;
    putenv($key.'='.$value);
}
\Stripe\ApiRequestor::setHttpClient(new class implements \Stripe\HttpClient\ClientInterface {
    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        throw new \LogicException('External Stripe HTTP forbidden in prestation tests.');
    }
});
