<?php

namespace App\Tests\Payment;

use App\Command\CleanupPendingAppointmentsCommand;
use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Tester\CommandTester;

final class CleanupPendingAppointmentsTest extends PaymentTestCase
{
    public static function expirationPolicies(): iterable
    {
        yield 'short threshold must not cancel prepared payment' => [5, '-10 minutes', '+20 minutes', false];
        yield 'long threshold must not extend prepared payment' => [60, '-40 minutes', '-10 minutes', true];
        yield 'legacy pending older than threshold' => [5, '-10 minutes', null, true];
        yield 'legacy pending newer than threshold' => [60, '-40 minutes', null, false];
    }

    #[DataProvider('expirationPolicies')]
    public function testCleanupAndDryRunUsePersistedExpiryOrLegacyThreshold(int $minutes, string $created, ?string $expiry, bool $cancel): void
    {
        if ($expiry !== null) {
            $this->prepare();
            $this->appointment->getPayment()->expiresAt = new \DateTimeImmutable($expiry);
        }
        $this->appointment->setCreatedAt(new \DateTimeImmutable($created));
        $this->em->flush();
        // Exercise the command's actual DQL on the isolated database.
        $repository = $this->createStub(AppointmentRepository::class);
        $repository->method('createQueryBuilder')->willReturnCallback(
            fn (string $alias) => $this->em->createQueryBuilder()->select($alias)->from(Appointment::class, $alias)
        );
        $tester = new CommandTester(new CleanupPendingAppointmentsCommand($repository, $this->payments));
        self::assertSame(0, $tester->execute(['--minutes' => $minutes, '--dry-run' => true]));
        self::assertSame(AppointmentStatus::PENDING, $this->persisted()->getStatus());
        self::assertStringContainsString($cancel ? '1 rendez-vous auraient été annulés' : 'Aucun rendez-vous', $tester->getDisplay());
        self::assertSame(0, $tester->execute(['--minutes' => $minutes]));
        self::assertSame($cancel ? AppointmentStatus::CANCELED : AppointmentStatus::PENDING, $this->persisted()->getStatus());
        self::assertSame(0, $this->mailCount());
    }

    public function testLockedRefreshDiscardsStaleExpiryForDryRunAndExecution(): void
    {
        $this->prepare();
        $this->appointment->setCreatedAt(new \DateTimeImmutable('-40 minutes'));
        $this->em->flush();
        foreach ([true, false] as $dryRun) {
            // Simulate selection before another transaction extended the persisted deadline.
            $this->appointment->getPayment()->expiresAt = new \DateTimeImmutable('-1 minute');
            self::assertFalse($this->payments->expire($this->appointment, new \DateTimeImmutable('-5 minutes'), new \DateTimeImmutable(), $dryRun));
            self::assertSame(AppointmentStatus::PENDING, $this->persisted()->getStatus());
        }
    }
}
