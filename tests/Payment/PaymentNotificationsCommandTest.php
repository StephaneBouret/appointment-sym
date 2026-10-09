<?php

namespace App\Tests\Payment;

use App\Command\PaymentNotificationsCommand;
use App\Event\AppointmentSuccessEvent;
use Symfony\Component\Console\Tester\CommandTester;

final class PaymentNotificationsCommandTest extends PaymentTestCase
{
    public function testCancellationBeforeQueuedDeliverySuppressesMail(): void
    {
        $intent = $this->prepare();
        $this->payments->confirmWebhook($intent);
        $this->appointment->setStatus(\App\Enum\AppointmentStatus::CANCELED);
        $this->em->flush();
        $tester = new CommandTester(new PaymentNotificationsCommand($this->em, $this->notifications));
        self::assertSame(0, $tester->execute(['--retry' => true]));
        self::assertSame('suppressed', $this->persisted()->getPayment()->notificationState);
        self::assertSame('confirmed_then_canceled', $this->payments->confirmWebhook($intent));
        self::assertSame(0, $tester->execute(['--retry' => true]));
        self::assertNull($this->persisted()->getPayment()->issue);
        self::assertSame(0, $this->mailCount());
    }

    public function testInspectionDoesNotSendAndRetryRecoversFailedMail(): void
    {
        $intent = $this->prepare();
        $failure = static function (): void { throw new \RuntimeException('mail test'); };
        $this->events->addListener(AppointmentSuccessEvent::NAME, $failure, 100);
        $this->payments->confirmWebhook($intent);
        $tester = new CommandTester(new PaymentNotificationsCommand($this->em, $this->notifications));
        self::assertSame('pending', $this->persisted()->getPayment()->notificationState);
        self::assertSame(0, $tester->execute(['--retry' => true]));
        $this->events->removeListener(AppointmentSuccessEvent::NAME, $failure);
        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('failed', $tester->getDisplay());
        self::assertSame(0, $this->mailCount());
        self::assertSame(0, $tester->execute(['--retry' => true]));
        self::assertSame('sent', $this->persisted()->getPayment()->notificationState);
        self::assertSame(1, $this->mailCount());
    }

    public function testUncertainDeliveryRequiresExplicitRecoveryAndAgeCheck(): void
    {
        $this->payments->confirmWebhook($this->prepare());
        $this->notifications->deliver($this->appointment);
        $this->appointment->getPayment()->notificationState = 'sending';
        $this->em->flush();
        $tester = new CommandTester(new PaymentNotificationsCommand($this->em, $this->notifications));
        $tester->execute(['--retry' => true]);
        self::assertSame(1, $this->mailCount());
        $tester->execute(['--retry-uncertain' => (string) $this->appointment->getId()]);
        self::assertSame('sending', $this->persisted()->getPayment()->notificationState);
        self::assertSame(1, $this->mailCount());
        $this->appointment->getPayment()->notificationAttemptedAt = new \DateTimeImmutable('-2 hours');
        $this->em->flush();
        $tester->execute(['--retry-uncertain' => (string) $this->appointment->getId()]);
        self::assertSame('sent', $this->persisted()->getPayment()->notificationState);
        self::assertSame(2, $this->mailCount());
    }
}
