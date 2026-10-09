<?php

namespace App\Tests\Payment;

use App\Service\AppointmentPaymentService;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class PaymentWiringTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRealContainerRoutesAndAnonymousWebhook(): void
    {
        // Never invoke config/bootstrap.php or Dotenv; never resolve a user's database URL.
        foreach ([
            'APP_ENV' => 'test', 'APP_DEBUG' => '0', 'APP_SECRET' => 'local-test-only',
            'DATABASE_URL' => 'sqlite:///:memory:', 'TEST_TOKEN' => '',
            'MAILER_DSN' => 'null://null', 'MAILER_DEFAULT_FROM' => 'sender@example.test',
            'MESSENGER_TRANSPORT_DSN' => 'in-memory://',
            'DEFAULT_URI' => 'https://example.test', 'APP_HOSTNAME' => 'https://example.test',
            'GOOGLE_API_KEY' => 'unused', 'GOOGLE_PLACE_ID' => 'unused',
            'STRIPE_SECRET_KEY' => 'sk_test_local_fake', 'STRIPE_PUBLIC_KEY' => 'pk_test_local_fake',
            'STRIPE_WEBHOOK_SECRET' => 'whsec_local_fake',
        ] as $name => $value) {
            $_SERVER[$name] = $_ENV[$name] = $value;
            putenv($name.'='.$value);
        }
        $kernel = new PaymentKernel('test', false);
        try {
            $kernel->boot();
            $container = $kernel->getContainer()->get('test.service_container');
            $em = $container->get('doctrine')->getManager();
            $params = $em->getConnection()->getParams();
            self::assertSame('pdo_sqlite', $params['driver']);
            self::assertTrue($params['memory'] ?? false);
            self::assertEmpty($params['path'] ?? null);
            // Only now can this test write a schema, to its validated in-memory connection.
            (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
            self::assertInstanceOf(AppointmentPaymentService::class, $container->get(AppointmentPaymentService::class));
            foreach (['2026-10-12' => '07:00', '2027-01-12' => '08:00'] as $day => $utcTime) {
                $form = $container->get('form.factory')->create(\App\Form\AppointmentFormType::class, new \App\Entity\Appointment());
                $form->get('startAt')->submit($day.'T09:00');
                $date = $form->get('startAt')->getData();
                self::assertSame('Europe/Paris', $date->getTimezone()->getName());
                self::assertSame($day.' '.$utcTime, $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i'));
            }
            $twig = $container->get('twig');
            foreach (['appointment/checkout.html.twig', 'appointment/payment.html.twig', 'appointment/list.html.twig',
                'appointment/pdf.html.twig', 'emails/appointment_success_user.html.twig'] as $template) {
                $twig->parse($twig->tokenize($twig->getLoader()->getSourceContext($template)));
                $this->addToAssertionCount(1);
            }
            $routes = $container->get('router')->getRouteCollection();
            self::assertSame(['POST'], $routes->get('app_stripe_webhook')->getMethods());
            self::assertSame(['POST'], $routes->get('app_appointment_confirm')->getMethods());
            foreach ([['/stripe/webhook', 'POST', 400], ['/stripe/webhook', 'GET', 405], ['/admin', 'GET', 302]] as [$path, $method, $status]) {
                $request = Request::create($path, $method);
                $response = $kernel->handle($request);
                self::assertSame($status, $response->getStatusCode());
                $kernel->terminate($request, $response);
            }
        } finally {
            $kernel->shutdown();
        }
    }
}
