<?php

declare(strict_types=1);

namespace App\Tests\PaymentMysql;

use App\Command\CleanupPendingAppointmentsCommand;
use App\Command\PaymentNotificationsCommand;
use App\Controller\StripeWebhookController;
use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/Support.php';

try {
    [$script, $manifest, $directory, $worker, $action, $appointmentId, $hold] = $argv;
    $target = readJson($manifest);
    check(realpath(dirname($manifest)) === realpath(dirname($directory)), 'Worker outside this run');
    $connection = guardedConnection($target, new BarrierMiddleware($directory, $worker, $hold));
    $em = entityManager($connection);
    signal($directory, $worker, 'connected', identity($connection));
    [$payments, $notifications] = services($em, $directory, $worker, in_array($hold, ['mail', 'lock-mail'], true));
    $appointment = $em->find(Appointment::class, (int) $appointmentId);
    check($appointment !== null, 'Missing fictitious appointment');
    $result = null;
    if ($action === 'webhook') {
        $body = json_encode(['id' => 'evt_fictitious_'.$worker, 'object' => 'event', 'type' => 'payment_intent.succeeded',
            'data' => ['object' => fakeIntent($appointment)->toArray()]], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $secret = 'local-fictitious-signature';
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
        $request = Request::create('/stripe/webhook', 'POST', server: ['HTTP_STRIPE_SIGNATURE' => $signature], content: $body);
        $result = (new StripeWebhookController($payments, $secret))($request)->getStatusCode();
        check($result === 204, 'Signed fake webhook failed');
    } elseif ($action === 'return') {
        $result = $payments->confirmReturn($appointment);
    } elseif ($action === 'cleanup') {
        $container = new Container();
        $container->set('recette.em', $em);
        $container->set('recette.connection', $connection);
        $registry = new Registry($container, ['default' => 'recette.connection'], ['default' => 'recette.em'], 'default', 'default');
        // Scope only the fixture; keep the production command's expiration DQL intact.
        $repository = new class($registry, (int) $appointmentId) extends AppointmentRepository {
            public function __construct(Registry $registry, private int $fixtureId) { parent::__construct($registry); }
            public function createQueryBuilder(string $alias, ?string $indexBy = null): \Doctrine\ORM\QueryBuilder
            {
                return parent::createQueryBuilder($alias, $indexBy)->andWhere($alias.'.id = :fixtureId')->setParameter('fixtureId', $this->fixtureId);
            }
        };
        $tester = new CommandTester(new CleanupPendingAppointmentsCommand($repository, $payments));
        $result = $tester->execute(['--minutes' => 60]);
        check($result === 0, 'Cleanup command failed');
        signal($directory, $worker, 'command', ['output' => $tester->getDisplay()]);
    } elseif ($action === 'notify') {
        $tester = new CommandTester(new PaymentNotificationsCommand($em, $notifications));
        $result = $tester->execute(['--retry' => true]);
        check($result === 0, 'Notification command failed');
    } else {
        throw new \RuntimeException('Unknown worker action');
    }
    $em->refresh($appointment);
    signal($directory, $worker, 'done', ['result' => $result, 'status' => $appointment->getStatus()->value,
        'number' => $appointment->getNumber(), 'issue' => $appointment->getPayment()->issue,
        'notification' => $appointment->getPayment()->notificationState]);
    $connection->close();
} catch (\Throwable $error) {
    // Do not print connection exceptions with usernames or connection parameters.
    fwrite(STDERR, $error::class.' at '.basename($error->getFile()).':'.$error->getLine()."\n");
    exit(1);
}
