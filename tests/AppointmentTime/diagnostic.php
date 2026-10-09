<?php
namespace App\Tests\AppointmentTime;

require_once dirname(__DIR__).'/Slots/local_migration.php';

function describe(?\DateTimeInterface $date): ?array
{
    return $date ? ['iso' => $date->format('Y-m-d\TH:i:sP'), 'timezone' => $date->getTimezone()->getName(), 'timestamp' => $date->getTimestamp()] : null;
}

function diagnose(\App\Kernel $kernel): array
{
    $kernel->boot();
    $container = $kernel->getContainer();
    $em = $container->get('doctrine')->getManager();
    $db = $em->getConnection();
    $db->executeStatement('SET TRANSACTION READ ONLY'); $db->beginTransaction();
    try {
        $target = \App\Tests\Slots\target($db);
        $raw = $db->fetchAssociative('SELECT id,number,start_at,end_at,status,payment_amount,payment_currency FROM appointment WHERE number = ?', ['20261008-10283']);
        \App\Tests\Slots\requireLocal((bool) $raw);
        $appointment = $em->find(\App\Entity\Appointment::class, $raw['id']);
        $controller = $container->get(\App\Controller\Appointment\AppointmentCheckoutController::class);
        $locator = (new \ReflectionProperty(\Symfony\Bundle\FrameworkBundle\Controller\AbstractController::class, 'container'))->getValue($controller);
        $form = $locator->get('form.factory')->create(\App\Form\AppointmentFormType::class, new \App\Entity\Appointment());
        $form->get('startAt')->submit('2026-10-12T09:00');
        $model = $form->get('startAt')->getData();
        $twig = $locator->get('twig');
        $formatter = new \IntlDateFormatter('fr', \IntlDateFormatter::LONG, \IntlDateFormatter::SHORT, new \DateTimeZone('Europe/Paris'));
        $render = fn ($value) => $twig->createTemplate("{{ d|date('d/m/Y H:i', 'Europe/Paris') }} | {{ d|format_datetime('long','short', timezone='Europe/Paris', locale='fr') }}")->render(['d' => $value]);
        return ['target' => $target, 'php_timezone' => date_default_timezone_get(), 'ini_timezone' => ini_get('date.timezone'),
            'php_ini' => php_ini_loaded_file(), 'intl_default_timezone' => \IntlTimeZone::createDefault()->getID(),
            'icu_version' => INTL_ICU_VERSION, 'intl_pattern' => $formatter->getPattern(), 'formatter_timezone' => $formatter->getTimeZoneId(),
            'twig_timezone' => $twig->getExtension(\Twig\Extension\CoreExtension::class)->getTimezone()->getName(),
            'mysql_timezone' => $db->fetchAssociative('SELECT @@session.time_zone AS session_timezone, @@system_time_zone AS system_timezone'),
            'raw' => $raw, 'hydrated_start' => describe($appointment->getStartAt()), 'hydrated_end' => describe($appointment->getEndAt()),
            'form_model_timezone' => $form->get('startAt')->getConfig()->getOption('model_timezone'),
            'form_view_timezone' => $form->get('startAt')->getConfig()->getOption('view_timezone'),
            'submitted_model' => describe($model), 'controller_utc' => describe($model->setTimezone(new \DateTimeZone('UTC'))),
            'doctrine_serialized' => \Doctrine\DBAL\Types\Type::getType('datetime_immutable')->convertToDatabaseValue($model->setTimezone(new \DateTimeZone('UTC')), $db->getDatabasePlatform()),
            'display_hydrated_start' => $render($appointment->getStartAt()),
            'display_raw_as_utc' => $render(new \DateTimeImmutable($raw['start_at'], new \DateTimeZone('UTC'))),
            'intl_object' => $formatter->format($appointment->getStartAt()),
            'intl_timestamp' => $formatter->format($appointment->getStartAt()->getTimestamp()),
        ];
    } finally { $db->rollBack(); }
}

if (PHP_SAPI !== 'cli' || realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) { return; }
try {
    (new \Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');
    echo json_encode(diagnose(new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG'])), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
} catch (\Throwable $error) { fwrite(STDERR, $error::class.' at '.basename($error->getFile()).':'.$error->getLine()."\n"); exit(1); }
