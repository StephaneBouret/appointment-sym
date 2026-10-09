<?php

namespace App\Command;

use App\Entity\Appointment;
use App\Service\PaymentNotificationService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:payments:notifications', description: 'Inspect payment incidents and retry durable confirmation notifications.')]
final class PaymentNotificationsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly PaymentNotificationService $notifications)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('retry', null, InputOption::VALUE_NONE, 'Send pending/failed notifications (may send email).')
            ->addOption('retry-uncertain', null, InputOption::VALUE_REQUIRED, 'Appointment ID: retry a stuck sending notification ONLY after checking mail delivery.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum rows', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (($id = $input->getOption('retry-uncertain')) !== null) {
            if (!ctype_digit((string) $id) || (int) $id < 1) {
                return Command::INVALID;
            }
            $appointment = $this->em->find(Appointment::class, (int) $id);
            if (!$appointment) {
                return Command::INVALID;
            }
            $this->em->wrapInTransaction(function () use ($appointment): void {
                $this->em->refresh($appointment, LockMode::PESSIMISTIC_WRITE);
                $payment = $appointment->getPayment();
                // Do not steal a delivery that may still be running.
                if ($payment->notificationState === 'sending' && $payment->notificationAttemptedAt !== null
                    && $payment->notificationAttemptedAt < new \DateTimeImmutable('-1 hour')) {
                    $payment->notificationState = 'failed';
                }
            });
            $this->notifications->deliver($appointment);
        }

        $retry = (bool) $input->getOption('retry');
        $appointments = $this->em->createQueryBuilder()->select('a')->from(Appointment::class, 'a')
            ->where($retry ? 'a.payment.notificationState IN (:states)' : 'a.payment.issue IS NOT NULL OR a.payment.notificationState IN (:states)')
            ->setParameter('states', $retry ? ['pending', 'failed'] : ['pending', 'failed', 'sending'])
            ->orderBy('a.payment.notificationAttemptedAt', 'ASC')->addOrderBy('a.id', 'ASC')
            ->setMaxResults(max(1, (int) $input->getOption('limit')))
            ->getQuery()->getResult();
        $rows = [];
        foreach ($appointments as $appointment) {
            if ($input->getOption('retry')) {
                $this->notifications->deliver($appointment);
            }
            $payment = $appointment->getPayment();
            $rows[] = [$appointment->getId(), $appointment->getStatus()->value, $payment->issue ?? '-',
                $payment->notificationState ?? '-', $payment->notificationError ?? '-'];
        }
        $io->table(['Appointment', 'Status', 'Payment issue', 'Notification', 'Error class'], $rows);
        return Command::SUCCESS;
    }
}
