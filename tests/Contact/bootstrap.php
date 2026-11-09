<?php

// Existing isolated environment: no Dotenv, SQLite memory, null mail, blocked Stripe HTTP.
require_once dirname(__DIR__).'/Prestations/bootstrap.php';
$_SERVER['TEST_TOKEN'] = $_ENV['TEST_TOKEN'] = 'contact_isolated';
putenv('TEST_TOKEN=contact_isolated');
date_default_timezone_set('UTC');
