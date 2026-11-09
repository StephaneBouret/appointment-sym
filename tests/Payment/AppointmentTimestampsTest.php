<?php

namespace App\Tests\Payment;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use PHPUnit\Framework\Attributes\DataProvider;

final class AppointmentTimestampsTest extends PaymentTestCase
{
    public function testConstructorProvidesBothCurrentImmutableTimestamps(): void
    {
        $before = new \DateTimeImmutable();
        $appointment = new Appointment();
        $after = new \DateTimeImmutable();
        foreach ([$appointment->getCreatedAt(), $appointment->getUpdatedAt()] as $date) {
            self::assertInstanceOf(\DateTimeImmutable::class, $date);
            self::assertGreaterThanOrEqual($before, $date);
            self::assertLessThanOrEqual($after, $date);
        }
        self::assertNull($appointment->getPayment()->expiresAt);
    }

    public static function timezones(): iterable
    {
        yield 'UTC' => ['UTC'];
        yield 'Paris' => ['Europe/Paris'];
    }

    #[DataProvider('timezones')]
    public function testHydrationAndExplicitUpdatePreserveTimestampLifecycle(string $timezone): void
    {
        $previous = date_default_timezone_get();
        date_default_timezone_set($timezone);
        try {
            $created = new \DateTimeImmutable('2026-01-12 10:00:00');
            $updated = new \DateTimeImmutable('2026-01-12 10:15:00');
            $this->appointment->setCreatedAt($created)->setUpdatedAt($updated);
            $this->em->flush();
            $this->reload();
            self::assertEquals($created, $this->appointment->getCreatedAt());
            self::assertEquals($updated, $this->appointment->getUpdatedAt());
            self::assertSame($timezone, $this->appointment->getCreatedAt()->getTimezone()->getName());

            // No automatic timestamp callback: an ordinary flush does not touch updatedAt.
            $this->appointment->setVisioUrl('https://example.test/meeting');
            $this->em->flush();
            $this->reload();
            self::assertEquals($created, $this->appointment->getCreatedAt());
            self::assertEquals($updated, $this->appointment->getUpdatedAt());

            $next = $updated->modify('+1 hour');
            $this->appointment->setUpdatedAt($next);
            $this->em->flush();
            $this->reload();
            self::assertEquals($created, $this->appointment->getCreatedAt());
            self::assertEquals($next, $this->appointment->getUpdatedAt());
        } finally {
            date_default_timezone_set($previous);
        }
    }

    public static function expirationBoundaries(): iterable
    {
        foreach ([false, true] as $explicit) {
            foreach ([-1, 0, 1] as $seconds) {
                yield ($explicit ? 'explicit' : 'legacy').' '.$seconds => [$explicit, $seconds];
            }
        }
    }

    #[DataProvider('expirationBoundaries')]
    public function testExpirationBoundaryDryRunAndRepetition(bool $explicit, int $seconds): void
    {
        $deadline = new \DateTimeImmutable('2026-11-09 12:00:00 UTC');
        $now = $deadline->modify(sprintf('%+d seconds', $seconds));
        // Explicit deadline takes priority, even when the legacy age is already expired.
        $created = $deadline->modify($explicit ? '-2 hours' : '-30 minutes');
        $updated = $created->modify('+1 minute');
        $this->appointment->setCreatedAt($created)->setUpdatedAt($updated);
        $this->appointment->getPayment()->expiresAt = $explicit ? $deadline : null;
        $this->em->flush();
        $this->reload();
        $before = $this->row();
        $threshold = $now->modify('-30 minutes');
        $expired = $seconds >= 0;

        self::assertSame($expired, $this->payments->expire($this->appointment, $threshold, $now, true));
        self::assertSame($before, $this->row(), 'Dry-run must not mutate any persisted field.');
        self::assertSame($expired, $this->payments->expire($this->appointment, $threshold, $now));
        $this->reload();
        self::assertSame($expired ? AppointmentStatus::CANCELED : AppointmentStatus::PENDING, $this->appointment->getStatus());
        self::assertEquals($created, $this->appointment->getCreatedAt());
        self::assertEquals($expired ? $now : $updated, $this->appointment->getUpdatedAt());
        self::assertEquals($explicit ? $deadline : null, $this->appointment->getPayment()->expiresAt);
        $after = $this->row();
        self::assertFalse($this->payments->expire($this->appointment, $threshold, $now));
        self::assertSame($after, $this->row());
        self::assertSame(0, $this->mailCount());
    }

    public static function terminalStatuses(): iterable
    {
        yield 'confirmed' => [AppointmentStatus::CONFIRMED];
        yield 'canceled' => [AppointmentStatus::CANCELED];
    }

    #[DataProvider('terminalStatuses')]
    public function testCleanupNeverMutatesTerminalAppointments(AppointmentStatus $status): void
    {
        $now = new \DateTimeImmutable('2026-11-09 12:00:00 UTC');
        $this->appointment->setStatus($status)->setCreatedAt($now->modify('-1 hour'))->setUpdatedAt($now->modify('-10 minutes'));
        $this->appointment->getPayment()->expiresAt = $now;
        $this->em->flush();
        $before = $this->row();
        foreach ([true, false, false] as $dryRun) {
            self::assertFalse($this->payments->expire($this->appointment, $now, $now, $dryRun));
            self::assertSame($before, $this->row());
        }
        self::assertSame(0, $this->mailCount());
    }

    public static function payableDeadlines(): iterable
    {
        yield 'legacy future' => ['2100-01-01 10:00:00', null, true];
        yield 'legacy past' => ['2000-01-01 10:00:00', null, false];
        yield 'explicit future overrides old creation' => ['2000-01-01 10:00:00', '2100-01-01 10:30:00', true];
        yield 'explicit past overrides future creation' => ['2100-01-01 10:00:00', '2000-01-01 10:30:00', false];
    }

    #[DataProvider('payableDeadlines')]
    public function testConsentPreservesDeadlinePolicyAndRepeatedAcceptance(string $creation, ?string $expiry, bool $payable): void
    {
        // canPay has no injectable clock; fixed dates far from now avoid timing-dependent tests.
        $created = new \DateTimeImmutable($creation, new \DateTimeZone('UTC'));
        $updated = $created->modify('+1 minute');
        $this->appointment->setCreatedAt($created)->setUpdatedAt($updated);
        $payment = $this->appointment->getPayment();
        $deadline = $expiry === null ? null : new \DateTimeImmutable($expiry, new \DateTimeZone('UTC'));
        if ($deadline !== null) {
            $payment->acceptedAt = $created;
            $payment->expiresAt = $deadline;
        }
        $this->em->flush();
        $before = $this->row();
        self::assertSame($payable, $this->payments->acceptTerms($this->appointment));
        $this->reload();
        $expected = $deadline ?? ($payable ? $created->modify('+30 minutes') : null);
        self::assertEquals($expected, $this->appointment->getPayment()->expiresAt);
        self::assertEquals($created, $this->appointment->getCreatedAt());
        self::assertEquals($updated, $this->appointment->getUpdatedAt());
        if (!$payable) {
            self::assertSame($before, $this->row());
        }
        $after = $this->row();
        self::assertSame($payable, $this->payments->acceptTerms($this->appointment));
        self::assertSame($after, $this->row());
        self::assertSame(0, $this->mailCount());
    }

    public function testConfirmationUpdatesOnlyUpdatedAtAndReplayKeepsItStable(): void
    {
        $intent = $this->prepare();
        $created = new \DateTimeImmutable('2000-01-01 10:00:00 UTC');
        $this->appointment->setCreatedAt($created)->setUpdatedAt($created);
        $this->appointment->getPayment()->expiresAt = new \DateTimeImmutable('2100-01-01 10:00:00 UTC');
        $this->em->flush();
        $before = new \DateTimeImmutable();
        self::assertSame('confirmed', $this->payments->confirmWebhook($intent));
        $after = new \DateTimeImmutable();
        $updated = $this->appointment->getUpdatedAt();
        self::assertGreaterThanOrEqual($before, $updated);
        self::assertLessThanOrEqual($after, $updated);
        $this->reload();
        self::assertEquals($created, $this->appointment->getCreatedAt());
        self::assertSame($updated->format('Y-m-d H:i:s'), $this->appointment->getUpdatedAt()->format('Y-m-d H:i:s'));
        $row = $this->row();
        self::assertSame('confirmed', $this->payments->confirmWebhook($intent));
        self::assertSame($row, $this->row());
        self::assertSame(0, $this->mailCount());
    }

    private function reload(): void
    {
        $id = $this->appointment->getId();
        $this->em->clear();
        $appointment = $this->em->find(Appointment::class, $id);
        self::assertInstanceOf(Appointment::class, $appointment);
        $this->appointment = $appointment;
    }

    private function row(): array
    {
        return $this->em->getConnection()->fetchAssociative('SELECT * FROM appointment WHERE id = ?', [$this->appointment->getId()]);
    }
}
