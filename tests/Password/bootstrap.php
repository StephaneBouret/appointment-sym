<?php

// Reuse the versioned fail-closed environment: no Dotenv, SQLite memory, no real mail or Stripe.
require_once dirname(__DIR__).'/Prestations/bootstrap.php';
require_once __DIR__.'/PasswordEffects.php';
foreach ([
    'TEST_TOKEN' => 'password_isolated',
    'APP_SECRET' => 'password-isolated-synthetic-secret-at-least-32-bytes',
    'GA4_ENABLED' => '0',
    'GA4_MEASUREMENT_ID' => '',
] as $key => $value) {
    $_SERVER[$key] = $_ENV[$key] = $value;
    putenv($key.'='.$value);
}
date_default_timezone_set('UTC');
