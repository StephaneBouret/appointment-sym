<?php

namespace App\Command;

use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:appointments:cleanup-pending',
    description: 'Annule les rendez-vous en statut PENDING trop anciens (ex: > 30 minutes).',
)]
final class CleanupPendingAppointmentsCommand extends Command
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // Délai d'expiration des PENDING
            ->addOption('minutes', null, InputOption::VALUE_REQUIRED, 'Âge max des PENDING (minutes)', '30')
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

        // Récupération des PENDING antérieurs au seuil (basé sur createdAt)
        // On fait une requête ciblée côté repo pour éviter de tout charger.
        $qb = $this->appointments->createQueryBuilder('a')
            ->andWhere('a.status = :pending')
            ->andWhere('a.createdAt <= :threshold')
            ->setParameter('pending', AppointmentStatus::PENDING)
            ->setParameter('threshold', $thresholdCreated)
            ->setMaxResults($limit);

        $toCancel = $qb->getQuery()->getResult();

        if (count($toCancel) === 0) {
            $io->success('Aucun rendez-vous PENDING à annuler.');
            return Command::SUCCESS;
        }

        $io->section(sprintf(
            'Trouvé %d RDV PENDING plus vieux que %d min (UTC<=%s). %s',
            count($toCancel),
            $minutes,
            $thresholdCreated->format('Y-m-d H:i:s'),
            $dryRun ? '[DRY-RUN]' : ''
        ));

        $count = 0;
        foreach ($toCancel as $appt) {
            // Optionnel : log/affichage
            /** @var \App\Entity\Appointment $appt */
            $io->text(sprintf(
                ' - #%s  start:%s  status:%s  created:%s',
                $appt->getId(),
                $appt->getStartAt()?->format('Y-m-d H:i') ?? 'n/a',
                $appt->getStatus()->value,
                $appt->getCreatedAt()?->format('Y-m-d H:i') ?? 'n/a'
            ));

            if ($dryRun) {
                // En dry-run, on n'annule pas
                continue;
            }

            // Annulation effective
            $appt->setStatus(AppointmentStatus::CANCELED);
            if (method_exists($appt, 'setUpdatedAt')) {
                $appt->setUpdatedAt($nowUtc);
            }

            // Flush par lot pour éviter la surcharge mémoire/transactions trop longues
            $this->em->persist($appt);
            $count++;

            if (($count % 50) === 0) {
                $this->em->flush();
                $this->em->clear(); // si nécessaire pour de très gros volumes
            }
        }

        if ($dryRun) {
            // Patch d'affichage : on indique combien de RDVs auraient été annulés
            $io->success(sprintf('DRY-RUN terminé : %d rendez-vous auraient été annulés.', count($toCancel)));
            return Command::SUCCESS;
        }

        // Commit final
        $this->em->flush();

        $io->success(sprintf('%d rendez-vous annulés avec succès.', $count));
        return Command::SUCCESS;
    }
}
