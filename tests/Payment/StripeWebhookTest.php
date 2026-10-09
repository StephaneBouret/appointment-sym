<?php

namespace App\Tests\Payment;

use App\Controller\StripeWebhookController;
use App\Enum\AppointmentStatus;
use Symfony\Component\HttpFoundation\Request;

final class StripeWebhookTest extends PaymentTestCase
{
    private const SIGNING_SECRET = 'whsec_local_test_only_not_a_real_secret';

    public function testMissingInvalidAndOldSignaturesCannotMutateAppointment(): void
    {
        $intent = $this->prepare();
        $body = $this->body($intent->toArray());
        $controller = new StripeWebhookController($this->payments, self::SIGNING_SECRET);
        foreach (['', 't='.time().',v1=invalid', $this->signature($body, time() - 600)] as $signature) {
            $request = Request::create('/stripe/webhook', 'POST', server: ['HTTP_STRIPE_SIGNATURE' => $signature], content: $body);
            self::assertSame(400, $controller($request)->getStatusCode());
        }
        self::assertSame(AppointmentStatus::PENDING, $this->persisted()->getStatus());
        self::assertNull($this->appointment->getNumber());
        self::assertSame(0, $this->mailCount());
    }

    public function testSignedRawBodyConfirmsWithoutSessionAndDeduplicatesDifferentEvents(): void
    {
        $intent = $this->prepare();
        $controller = new StripeWebhookController($this->payments, self::SIGNING_SECRET);
        foreach (['evt_test_one', 'evt_test_one', 'evt_test_two'] as $eventId) {
            $body = $this->body($intent->toArray(), $eventId);
            $request = Request::create('/stripe/webhook', 'POST', server: ['HTTP_STRIPE_SIGNATURE' => $this->signature($body)], content: $body);
            self::assertFalse($request->hasSession());
            self::assertSame(204, $controller($request)->getStatusCode());
        }
        self::assertSame(AppointmentStatus::CONFIRMED, $this->persisted()->getStatus());
        self::assertNotNull($this->appointment->getNumber());
        self::assertSame('pending', $this->appointment->getPayment()->notificationState);
        self::assertSame(0, $this->mailCount());
        self::assertSame([], $this->deliveredNumbers);
        self::assertNull($this->appointment->getPayment()->notificationAttemptedAt);
        $tester = new \Symfony\Component\Console\Tester\CommandTester(
            new \App\Command\PaymentNotificationsCommand($this->em, $this->notifications)
        );
        self::assertSame(0, $tester->execute(['--retry' => true]));
        self::assertSame(0, $tester->execute(['--retry' => true]));
        self::assertSame('sent', $this->persisted()->getPayment()->notificationState);
        self::assertSame(1, $this->mailCount());
    }

    public function testModifiedBodyAndSignedMalformedJsonAreRejected(): void
    {
        $intent = $this->prepare();
        $body = $this->body($intent->toArray());
        $controller = new StripeWebhookController($this->payments, self::SIGNING_SECRET);
        $tampered = Request::create('/stripe/webhook', 'POST', server: ['HTTP_STRIPE_SIGNATURE' => $this->signature($body)], content: $body.' ');
        self::assertSame(400, $controller($tampered)->getStatusCode());
        $malformed = Request::create('/stripe/webhook', 'POST', server: ['HTTP_STRIPE_SIGNATURE' => $this->signature('{')], content: '{');
        self::assertSame(400, $controller($malformed)->getStatusCode());
        self::assertSame(AppointmentStatus::PENDING, $this->persisted()->getStatus());
        self::assertSame(0, $this->mailCount());
    }

    public function testOtherEventTypesAreAcknowledgedWithoutConfirmation(): void
    {
        $intent = $this->prepare();
        $body = $this->body($intent->toArray(), type: 'payment_intent.payment_failed');
        $request = Request::create('/stripe/webhook', 'POST', server: ['HTTP_STRIPE_SIGNATURE' => $this->signature($body)], content: $body);
        self::assertSame(204, (new StripeWebhookController($this->payments, self::SIGNING_SECRET))($request)->getStatusCode());
        self::assertSame(AppointmentStatus::PENDING, $this->persisted()->getStatus());
        self::assertSame(0, $this->mailCount());
    }

    private function body(array $intent, string $id = 'evt_test', string $type = 'payment_intent.succeeded'): string
    {
        return json_encode(['id' => $id, 'object' => 'event', 'type' => $type, 'data' => ['object' => $intent]], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    private function signature(string $body, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, self::SIGNING_SECRET);
    }
}
