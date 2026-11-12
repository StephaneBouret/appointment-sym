<?php

// Safe environment, null transport and forbidden Stripe HTTP; never load .env.
require_once dirname(__DIR__).'/Prestations/bootstrap.php';
require_once __DIR__.'/PhoneEffects.php';
$_SERVER['TEST_TOKEN'] = $_ENV['TEST_TOKEN'] = 'phone_isolated';
putenv('TEST_TOKEN=phone_isolated');
date_default_timezone_set('UTC');
