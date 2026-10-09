<?php

namespace App\Tests\Payment;

use App\Enum\AppointmentStatus;
use App\Event\AppointmentSuccessEvent;
use PHPUnit\Framework\Attributes\DataProvider;

final class AppointmentPaymentTest extends PaymentTestCase
{
    public static function notificationHistory(): iterable
    {
        foreach (['pending', 'sent', 'failed', 'sending', 'suppressed'] as $state) {
            yield $state => [$state];
        }
    }

    #[DataProvider('notificationHistory')]
    public function testRepeatedVerifiedPaymentAfterCancellationPreservesHistory(string $state): void
    {
        $intent = $this->prepare();
        self::assertSame('confirmed', $this->payments->confirmWebhook($intent));
        $this->appointment->setStatus(AppointmentStatus::CANCELED);
        $payment = $this->appointment->getPayment();
        $payment->notificationState = $state;
        $payment->notificationAttemptedAt = new \DateTimeImmutable('-2 hours');
        $payment->notificationSentAt = $state === 'sent' ? new \DateTimeImmutable('-1 hour') : null;
        $payment->notificationError = $state === 'failed' ? \RuntimeException::class : null;
        $this->em->flush();
        $before = clone $this->persisted()->getPayment();
        $number = $this->appointment->getNumber();
        $updatedAt = $this->appointment->getUpdatedAt();
        $mailCount = $this->mailCount();
        $this->stripe->method('retrievePaymentIntent')->willReturn($intent);
        self::assertSame('confirmed_then_canceled', $this->payments->confirmWebhook($intent));
        self::assertSame('confirmed_then_canceled', $this->payments->confirmReturn($this->appointment));
        self::assertSame(AppointmentStatus::CANCELED, $this->persisted()->getStatus());
        self::assertEquals($before, $this->appointment->getPayment());
        self::assertSame($number, $this->appointment->getNumber());
        self::assertEquals($updatedAt, $this->appointment->getUpdatedAt());
        self::assertNull($this->appointment->getPayment()->issue);
        self::assertSame($mailCount, $this->mailCount());
    }

    public function testDirectReturnCannotConfirmOrCallStripe(): void
    {
        $this->mockStripe();
        $this->stripe->expects(self::never())->method('retrievePaymentIntent');
        self::assertSame('unverified', $this->payments->confirmReturn($this->appointment));
        self::assertSame(AppointmentStatus::PENDING, $this->persisted()->getStatus());
        self::assertNull($this->appointment->getNumber());
        self::assertSame(0, $this->mailCount());
    }

    public function testCreationNeedsConsentAndReusesIntentAndFrozenAmount(): void
    {
        $this->mockStripe();
        self::assertNull($this->payments->getOrCreateIntent($this->appointment));
        self::assertTrue($this->payments->acceptTerms($this->appointment));
        $key = $this->appointment->getPayment()->attemptKey;
        $this->appointment->getType()->setPrice(22000);
        $this->em->flush();
        $intent = $this->intent();
        $this->stripe->expects(self::once())->method('createAppointmentIntent')->willReturnCallback(function ($appointment) use ($intent, $key) {
            self::assertSame(15000, $appointment->getPayment()->amount);
            self::assertSame($key, $appointment->getPayment()->attemptKey);
            return $intent;
        });
        $this->stripe->expects(self::once())->method('retrievePaymentIntent')->with($intent->id)->willReturn($intent);
        $this->payments->getOrCreateIntent($this->appointment);
        $this->payments->getOrCreateIntent($this->appointment);
        self::assertSame($intent->id, $this->persisted()->getPayment()->intentId);
        self::assertSame(15000, $this->appointment->getPayableAmount());
    }

    public function testSuccessfulWebhookWithoutBrowserAndRepeatedConfirmation(): void
    {
        $intent = $this->prepare();
        self::assertSame('confirmed', $this->payments->confirmWebhook($intent));
        $number = $this->persisted()->getNumber();
        self::assertNotNull($number);
        self::assertNotNull($this->appointment->getPayment()->verifiedAt);
        self::assertSame('pending', $this->appointment->getPayment()->notificationState);
        self::assertSame(0, $this->mailCount());
        self::assertSame(AppointmentStatus::CONFIRMED, $this->appointment->getStatus());
        self::assertSame('confirmed', $this->payments->confirmWebhook($intent));
        $this->mockStripe();
        $this->stripe->expects(self::once())->method('retrievePaymentIntent')->with($intent->id)->willReturn($intent);
        self::assertSame('confirmed', $this->payments->confirmReturn($this->appointment));
        self::assertSame($number, $this->persisted()->getNumber());
        self::assertSame([$number], $this->deliveredNumbers);
        self::assertSame(1, $this->mailCount());
    }

    public function testCreationFailureKeepsTheDurableKeyForTheNextRequest(): void
    {
        $this->mockStripe();
        $this->payments->acceptTerms($this->appointment);
        $id = $this->appointment->getId();
        $key = $this->appointment->getPayment()->attemptKey;
        $intent = $this->intent();
        $connection = $this->em->getConnection();
        $config = $this->em->getConfiguration();
        $this->stripe->expects(self::once())->method('createAppointmentIntent')
            ->willThrowException(\Stripe\Exception\ApiConnectionException::factory('simulated timeout'));
        try {
            $this->payments->getOrCreateIntent($this->appointment);
            self::fail('Expected simulated timeout');
        } catch (\Stripe\Exception\ApiConnectionException) {
            self::assertSame($key, $connection->fetchOne('SELECT payment_attempt_key FROM appointment WHERE id = ?', [$id]));
            self::assertNull($connection->fetchOne('SELECT payment_intent_id FROM appointment WHERE id = ?', [$id]));
        }
        // New request/EntityManager, same isolated database; no new acceptance or key.
        $this->em = new \Doctrine\ORM\EntityManager($connection, $config);
        $this->appointment = $this->em->find(\App\Entity\Appointment::class, $id);
        $this->notifications = new \App\Service\PaymentNotificationService($this->em, $this->events, new \Psr\Log\NullLogger());
        $this->mockStripe();
        $this->stripe->expects(self::once())->method('createAppointmentIntent')->willReturnCallback(function ($appointment) use ($key, $intent) {
            self::assertSame($key, $appointment->getPayment()->attemptKey);
            return $intent;
        });
        $this->payments->getOrCreateIntent($this->appointment);
        self::assertSame($intent->id, $this->persisted()->getPayment()->intentId);
        self::assertSame(0, $this->mailCount());
    }

    public function testCanceledReplayStillChecksAssociationAndAmount(): void
    {
        $this->payments->confirmWebhook($this->prepare());
        $this->appointment->setStatus(AppointmentStatus::CANCELED);
        $this->em->flush();
        $number = $this->persisted()->getNumber();
        self::assertSame('association_mismatch', $this->payments->confirmWebhook($this->intent(overrides: ['metadata' => ['kind' => 'other']])));
        self::assertSame('amount_or_currency_mismatch', $this->payments->confirmWebhook($this->intent(overrides: ['amount_received' => 1])));
        self::assertSame(AppointmentStatus::CANCELED, $this->persisted()->getStatus());
        self::assertSame($number, $this->appointment->getNumber());
        self::assertSame('pending', $this->appointment->getPayment()->notificationState);
        self::assertSame(0, $this->mailCount());
    }

    public function testPayableAmountFallsBackForManualAppointmentWithoutProof(): void
    {
        $this->appointment->setStatus(AppointmentStatus::CONFIRMED)->setNumber('manual-test');
        self::assertSame(15000, $this->appointment->getPayableAmount());
        self::assertNull($this->appointment->getPayment()->verifiedAt);
        $this->appointment->getType()->setPrice(22000);
        self::assertSame(22000, $this->appointment->getPayableAmount());
        $this->appointment->getPayment()->amount = 15000;
        self::assertSame(15000, $this->appointment->getPayableAmount());
    }

    public static function invalidPayments(): iterable
    {
        yield 'amount' => [['amount' => 100], 'amount_or_currency_mismatch'];
        yield 'amount received' => [['amount_received' => 100], 'amount_or_currency_mismatch'];
        yield 'currency' => [['currency' => 'usd'], 'amount_or_currency_mismatch'];
        yield 'other appointment metadata' => [['metadata' => ['appointment_id' => '999']], 'association_mismatch'];
        foreach (['requires_capture', 'processing', 'requires_payment_method', 'requires_action', 'requires_confirmation', 'canceled'] as $status) {
            yield $status => [['status' => $status], 'unpaid'];
        }
    }

    #[DataProvider('invalidPayments')]
    public function testInvalidPaymentNeverConfirms(array $overrides, string $result): void
    {
        $this->prepare();
        self::assertSame($result, $this->payments->confirmWebhook($this->intent(overrides: $overrides)));
        self::assertSame(AppointmentStatus::PENDING, $this->persisted()->getStatus());
        self::assertNull($this->appointment->getNumber());
        self::assertNull($this->appointment->getPayment()->verifiedAt);
        self::assertSame(0, $this->mailCount());
    }

    public function testAnotherAppointmentsPaymentCannotConfirmThisOne(): void
    {
        $this->prepare();
        $other = $this->newAppointment();
        $otherIntent = $this->prepare($other);
        $this->stripe->method('retrievePaymentIntent')->willReturn($otherIntent);
        self::assertSame('association_mismatch', $this->payments->confirmReturn($this->appointment));
        self::assertSame(AppointmentStatus::PENDING, $this->persisted()->getStatus());
        self::assertSame(AppointmentStatus::PENDING, $other->getStatus());
        self::assertSame(0, $this->mailCount());
    }

    public function testUnassociatedLegacyIntentIsNotAttachedByMetadata(): void
    {
        self::assertSame('unassociated', $this->payments->confirmWebhook($this->intent()));
        self::assertNull($this->persisted()->getPayment()->intentId);
        self::assertSame(0, $this->mailCount());
    }

    public static function lateStates(): iterable
    {
        yield 'canceled' => [true];
        yield 'expired without cleanup' => [false];
    }

    #[DataProvider('lateStates')]
    public function testLatePaymentIsFlaggedWithoutReoccupyingSlot(bool $canceled): void
    {
        $intent = $this->prepare();
        if ($canceled) {
            $this->appointment->setStatus(AppointmentStatus::CANCELED);
        } else {
            $this->appointment->getPayment()->expiresAt = new \DateTimeImmutable('-1 minute');
        }
        $this->em->flush();
        self::assertSame('late_payment', $this->payments->confirmWebhook($intent));
        self::assertSame('late_payment', $this->payments->confirmWebhook($intent));
        $this->stripe->method('retrievePaymentIntent')->willReturn($intent);
        self::assertSame('late_payment', $this->payments->confirmReturn($this->appointment));
        self::assertSame(AppointmentStatus::CANCELED, $this->persisted()->getStatus());
        self::assertSame('late_payment', $this->appointment->getPayment()->issue);
        self::assertNotNull($this->appointment->getPayment()->verifiedAt);
        self::assertNull($this->appointment->getNumber());
        self::assertSame(0, $this->mailCount());
    }

    public function testCleanupRefreshesStaleStateAfterConfirmation(): void
    {
        $intent = $this->prepare();
        $this->payments->confirmWebhook($intent);
        // Simulate an earlier cleanup read; lock+refresh must discard it.
        foreach ([true, false] as $dryRun) {
            $this->appointment->setStatus(AppointmentStatus::PENDING);
            self::assertFalse($this->payments->expire($this->appointment, new \DateTimeImmutable('+1 minute'), new \DateTimeImmutable('+1 hour'), $dryRun));
        }
        self::assertSame(AppointmentStatus::CONFIRMED, $this->persisted()->getStatus());
        self::assertSame(0, $this->mailCount());
    }

    public function testCleanupBeforeWebhookKeepsAppointmentCanceled(): void
    {
        $intent = $this->prepare();
        $this->appointment->getPayment()->expiresAt = new \DateTimeImmutable('-1 minute');
        $this->em->flush();
        self::assertTrue($this->payments->expire($this->appointment, new \DateTimeImmutable('+1 minute')));
        self::assertSame('late_payment', $this->payments->confirmWebhook($intent));
        self::assertSame(AppointmentStatus::CANCELED, $this->persisted()->getStatus());
        self::assertSame(0, $this->mailCount());
    }

    public function testMailFailureIsDurableAndRetryDoesNotRegenerateNumber(): void
    {
        $intent = $this->prepare();
        $failure = static function (): void { throw new \RuntimeException('simulated mail failure'); };
        $this->events->addListener(AppointmentSuccessEvent::NAME, $failure, 100);
        self::assertSame('confirmed', $this->payments->confirmWebhook($intent));
        $this->notifications->deliver($this->appointment);
        $number = $this->persisted()->getNumber();
        self::assertSame('failed', $this->appointment->getPayment()->notificationState);
        self::assertSame(\RuntimeException::class, $this->appointment->getPayment()->notificationError);
        self::assertSame(0, $this->mailCount());
        $this->events->removeListener(AppointmentSuccessEvent::NAME, $failure);
        $this->notifications->deliver($this->appointment);
        $this->notifications->deliver($this->appointment);
        self::assertSame('sent', $this->persisted()->getPayment()->notificationState);
        self::assertSame($number, $this->appointment->getNumber());
        self::assertSame(1, $this->mailCount());
    }

    public function testClaimedNotificationIsNotSentAgain(): void
    {
        $intent = $this->prepare();
        $this->payments->confirmWebhook($intent);
        $this->appointment->getPayment()->notificationState = 'sending';
        $this->em->flush();
        $this->payments->confirmWebhook($intent);
        self::assertSame('sending', $this->persisted()->getPayment()->notificationState);
        $this->notifications->deliver($this->appointment);
        self::assertSame(0, $this->mailCount());
    }

    public function testLegacyManualAndFreeAppointmentsAreNotMarkedStripeVerified(): void
    {
        $this->appointment->getType()->setPrice(0);
        $this->em->flush();
        self::assertFalse($this->payments->acceptTerms($this->appointment));
        $this->appointment->setStatus(AppointmentStatus::CONFIRMED)->setNumber('manual-test');
        $this->em->flush();
        self::assertSame('unverified', $this->payments->confirmReturn($this->appointment));
        self::assertSame('manual-test', $this->persisted()->getNumber());
        self::assertNull($this->appointment->getPayment()->verifiedAt);
        self::assertSame(0, $this->mailCount());
    }
}
