<?php

// Safe values, no Dotenv, null mail transport and forbidden external Stripe HTTP.
require_once dirname(__DIR__).'/Prestations/bootstrap.php';
// Non-empty value also lets lint:container resolve Doctrine's test suffix.
$_SERVER['TEST_TOKEN'] = $_ENV['TEST_TOKEN'] = 'mail_isolated';
putenv('TEST_TOKEN=mail_isolated');
date_default_timezone_set('UTC');
