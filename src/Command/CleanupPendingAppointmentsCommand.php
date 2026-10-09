<?php

namespace App\Command;

use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use App\Service\AppointmentPaymentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:appointments:cleanup-pending',
    description: 'Annule les PENDING à échéance ; sans échéance, utilise leur ancienneté.',
)]
final class CleanupPendingAppointmentsCommand extends Command
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly AppointmentPaymentService $payments,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('minutes', null, InputOption::VALUE_REQUIRED, 'Âge max des PENDING sans échéance persistée (minutes)', (string) AppointmentPaymentService::PENDING_MINUTES)
            // Sécurité/diagnostic
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'N\'annule rien, affiche seulement ce qui serait fait')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre max de RDV à traiter', '500');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $minutes = (int) $input->getOption('minutes');
        $limit   = max(1, (int) $input->getOption('limit'));
        $dryRun  = (bool) $input->getOption('dry-run');

        if ($minutes <= 0) {
            $io->error('L\'option --minutes doit être > 0.');
            return Command::INVALID;
        }

        // IMPORTANT : nous stockons en UTC (subscriber). On calcule donc le seuil en UTC.
        $nowUtc           = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $thresholdCreated = $nowUtc->modify("-{$minutes} minutes");

        // The persisted deadline takes priority over the legacy age threshold.
        $qb = $this->appointments->createQueryBuilder('a')
            ->andWhere('a.status = :pending')
            ->andWhere('(a.payment.expiresAt <= :now OR (a.payment.expiresAt IS NULL AND a.createdAt <= :threshold))')
            ->setParameter('pending', AppointmentStatus::PENDING)
            ->setParameter('now', $nowUtc)
            ->setParameter('threshold', $thresholdCreated)
            ->setMaxResults($limit);

        $toCancel = $qb->getQuery()->getResult();

        if (count($toCancel) === 0) {
            $io->success('Aucun rendez-vous PENDING à annuler.');
            return Command::SUCCESS;
        }

        $io->section(sprintf(
            'Trouvé %d RDV PENDING à échéance ; repli sans échéance : %d min (création UTC<=%s). %s',
            count($toCancel),
            $minutes,
            $thresholdCreated->format('Y-m-d H:i:s'),
            $dryRun ? '[DRY-RUN]' : ''
        ));

        $count = 0;
        foreach ($toCancel as $appt) {
            // Dry-run uses the same locked refresh and eligibility decision.
            if (!$this->payments->expire($appt, $thresholdCreated, $nowUtc, $dryRun)) {
                continue;
            }
            $count++;
            // Optionnel : log/affichage
            /** @var \App\Entity\Appointment $appt */
            $io->text(sprintf(
                ' - #%s  start:%s  status:%s  created:%s',
                $appt->getId(),
                $appt->getStartAt()?->format('Y-m-d H:i') ?? 'n/a',
                $appt->getStatus()->value,
                $appt->getCreatedAt()?->format('Y-m-d H:i') ?? 'n/a'
            ));

        }

        if ($dryRun) {
            $io->success(sprintf('DRY-RUN terminé : %d rendez-vous auraient été annulés.', $count));
            return Command::SUCCESS;
        }

        $io->success(sprintf('%d rendez-vous annulés avec succès.', $count));
        return Command::SUCCESS;
    }
}
