<?php

namespace App\Command;

use DateInterval;
use DateTimeZone;
use DateTimeImmutable;
use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AppointmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:send-appointment-email-reminders',
    description: 'Envoie les emails de rappel pour les rendez-vous (J-7 et J-1).'
)]
final class SendAppointmentEmailRemindersCommand extends Command
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly EntityManagerInterface $em,
        private readonly SendMailService $mailService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // On travaille en UTC
        $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $window = new DateInterval('PT15M'); // fenêtre de 15 minutes

        // J-7
        $this->processWindow($nowUtc, $window, 7, $output);

        // J-1 (24h)
        $this->processWindow($nowUtc, $window, 1, $output);

        return Command::SUCCESS;
    }

    private function processWindow(
        DateTimeImmutable $nowUtc,
        DateInterval $window,
        int $daysBefore,
        OutputInterface $output
    ): void {
        $start = $nowUtc->add(new DateInterval('P' . $daysBefore . 'D'));
        $end = $start->add($window);

        $output->writeln(sprintf(
            '[%s] Recherche des rendez-vous pour J-%d entre %s et %s (UTC)…',
            (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            $daysBefore,
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
        ), OutputInterface::VERBOSITY_VERBOSE);

        $qb = $this->appointmentRepository->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', AppointmentStatus::CONFIRMED)
            ->andWhere('a.startAt >= :start AND a.startAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($daysBefore === 7) {
            $qb->andWhere('a.reminder7SentAt IS NULL');
        } else {
            $qb->andWhere('a.reminder24SentAt IS NULL');
        }

        /** @var Appointment[] $appointments */
        $appointments = $qb->getQuery()->getResult();

        if (!$appointments) {
            $output->writeln(
                sprintf('Aucun rendez-vous à notifier pour J-%d.', $daysBefore),
                OutputInterface::VERBOSITY_VERBOSE
            );
            return;
        }

        $output->writeln(sprintf(
            'Envoi des rappels J-%d pour %d rendez-vous…',
            $daysBefore,
            \count($appointments)
        ), OutputInterface::VERBOSITY_VERBOSE);

        $myAppointmentsUrl = $this->urlGenerator->generate('app_appointment_list', [], UrlGeneratorInterface::ABSOLUTE_URL);

        foreach ($appointments as $appointment) {
            $user = $appointment->getUser();

            if (!$user || !$user->getEmail()) {
                $output->writeln(
                    sprintf('RDV #%d : utilisateur ou email manquant, rappel ignoré.', $appointment->getNumber()),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                continue;
            }

            // Sujet de l'email
            $subject = $daysBefore === 7
                ? 'Rappel : votre rendez-vous dans 7 jours'
                : 'Rappel : votre rendez-vous demain';

            // Envoi via le service
            $this->mailService->sendMail(
                null,
                'L\'Univers des nombres',
                $user->getEmail(),
                $subject,
                'appointment_reminder',
                [
                    'user' => $user,
                    'appointment' => $appointment,
                    'daysBefore' => $daysBefore,
                    'myAppointmentsUrl' => $myAppointmentsUrl,
                ]
            );

            $nowMarker = new DateTimeImmutable('now', new DateTimeZone('UTC'));

            if ($daysBefore === 7) {
                $appointment->setReminder7SentAt($nowMarker);
            } else {
                $appointment->setReminder24SentAt($nowMarker);
            }
        }

        $this->em->flush();

        $output->writeln(sprintf(
            'Rappels J-%d envoyés et marqués comme traités.',
            $daysBefore
        ), OutputInterface::VERBOSITY_VERBOSE);
    }
}
