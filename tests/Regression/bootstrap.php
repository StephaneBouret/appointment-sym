<?php

// Reuse the audited, fail-closed test environment: no Dotenv, SQLite memory,
// null mail, in-memory Messenger, fake credentials, blocked Stripe HTTP.
require_once dirname(__DIR__).'/Prestations/bootstrap.php';
date_default_timezone_set('UTC');
