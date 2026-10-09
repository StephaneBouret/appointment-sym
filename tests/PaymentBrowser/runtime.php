<?php

namespace App\Tests\PaymentBrowser;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';
require_once __DIR__.'/Kernel.php';
date_default_timezone_set('UTC');

function state(): string
{
    return str_replace('\\', '/', getenv('LOCALAPPDATA')).'/Temp/appointment-payment-browser-20261007';
}
function readState(string $file): array { return json_decode(file_get_contents(state().'/'.$file), true, flags: JSON_THROW_ON_ERROR); }
function saveState(string $file, array $data): void { file_put_contents(state().'/'.$file, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)); }
function ensure(bool $ok, string $message): void { if (!$ok) { throw new \RuntimeException($message); } }
function stripeReady(): bool
{
    if (!is_file(state().'/stripe-private.json')) { return false; }
    $keys = readState('stripe-private.json');
    return preg_match('/^sk_test_[A-Za-z0-9]+$/D', $keys['secret'] ?? '')
        && preg_match('/^pk_test_[A-Za-z0-9]+$/D', $keys['public'] ?? '')
        && preg_match('/^whsec_[A-Za-z0-9]+$/D', $keys['webhook'] ?? '');
}
function guard(\Doctrine\DBAL\Connection $connection): array
{
    $expected = readState('target.json');
    $actual = $connection->fetchAssociative('SELECT DATABASE() AS db, @@hostname AS server, @@server_uuid AS uuid, @@port AS port, VERSION() AS version, @@default_storage_engine AS engine, CONNECTION_ID() AS connection_id');
    ensure($actual['db'] === $expected['database'] && preg_match('/^appointment_payment_browser_[0-9]{8}_[a-f0-9]{12}$/D', $actual['db']), 'Wrong effective database');
    ensure($actual['uuid'] === $expected['uuid'] && (int) $actual['port'] === 3306 && $actual['engine'] === 'InnoDB', 'Wrong effective server/engine');
    ensure((int) $connection->fetchOne('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND ENGINE <> ?', [$actual['db'], 'InnoDB']) === 0, 'Unexpected table engine');
    return $actual;
}
function boot(): Kernel
{
    $target = readState('target.json');
    $keys = stripeReady() ? readState('stripe-private.json') : ['secret' => 'sk_test_unconfigured', 'public' => 'pk_test_unconfigured', 'webhook' => ''];
    foreach (['APP_ENV' => 'recette', 'APP_DEBUG' => '1', 'APP_SECRET' => $target['session_key'],
        'DATABASE_URL' => 'mysql://root@127.0.0.1:3306/'.$target['database'].'?charset=utf8mb4',
        'MAILER_DSN' => 'null://null', 'MAILER_DEFAULT_FROM' => 'sender@example.test',
        'MESSENGER_TRANSPORT_DSN' => 'in-memory://', 'DEFAULT_URI' => 'http://127.0.0.1:8097',
        'APP_HOSTNAME' => 'http://127.0.0.1:8097', 'GOOGLE_API_KEY' => 'unused', 'GOOGLE_PLACE_ID' => 'unused',
        'STRIPE_SECRET_KEY' => $keys['secret'], 'STRIPE_PUBLIC_KEY' => $keys['public'], 'STRIPE_WEBHOOK_SECRET' => $keys['webhook']] as $key => $value) {
        $_SERVER[$key] = $_ENV[$key] = $value;
        putenv($key.'='.$value);
    }
    $kernel = new Kernel('recette', true);
    $kernel->boot();
    $container = $kernel->getContainer()->get('test.service_container');
    guard($container->get('doctrine')->getConnection());
    ensure($container->get('mailer.transports') instanceof CaptureTransport, 'External mail transport forbidden');
    return $kernel;
}
function snapshot(Kernel $kernel): array
{
    $db = $kernel->getContainer()->get('doctrine')->getConnection();
    return ['target' => guard($db), 'stripe_test_configured' => stripeReady(), 'mail_transport' => 'local-files-only',
        'appointments' => $db->fetchAllAssociative('SELECT id, user_id, status, number, payment_intent_id, payment_amount, payment_currency, payment_verified_at, payment_issue, payment_notification_state, payment_notification_sent_at FROM appointment ORDER BY id'),
        'captured_mail_count' => count(glob(state().'/mail/*.json'))];
}

function completeCompany(\Doctrine\ORM\EntityManagerInterface $em): void
{
    if ($em->getRepository(\App\Entity\Company::class)->find(1)) { return; }
    $company = (new \App\Entity\Company())->setName('Entreprise fictive de recette')->setSlug('recette')
        ->setAdress('1 rue fictive')->setPostalCode('75001')->setCity('Paris')->setEmail('company@example.test')
        ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0600000000', 'FR'))
        ->setType(\App\Entity\Company::TYPE_SAS)->setSiren('000000000')->setUrl('https://example.test')->setManager('Personne fictive');
    $em->persist($company);
    $em->flush();
}
