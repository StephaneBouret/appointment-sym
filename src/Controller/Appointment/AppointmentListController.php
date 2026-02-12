<?php

namespace App\Controller\Appointment;

use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Service\ScheduleSettingService;
use App\Repository\AppointmentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/rendez-vous')]
final class AppointmentListController extends AbstractController
{
    #[Route('/list', name: 'app_appointment_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page')]
    public function index(AppointmentRepository $repo, ScheduleSettingService $settings): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $openingDelayHours = $settings->getInt('opening_delay_hours', 48);
        $openDaysCsv       = $settings->get('open_days', '1,2,3,4,5');
        $rescheduleMinNoticeHours = $settings->getInt('reschedule_min_notice_hours', 24);

        $appointments = $repo->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.status IN (:statuses)')
            ->andWhere('a.number IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('statuses', [AppointmentStatus::CONFIRMED, AppointmentStatus::CANCELED])
            ->orderBy('a.startAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('appointment/list.html.twig', [
            'appointments' => $appointments,
            'openingDelayHours' => $openingDelayHours,
            'openDaysCsv'       => $openDaysCsv,
            'rescheduleMinNoticeHours' => $rescheduleMinNoticeHours,
        ]);
    }
}
