<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\AppointmentType;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function hasUserCompletedType(User $user, AppointmentType $type): bool
    {
        return (bool) $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.type = :type')
            ->andWhere('a.status IN (:status)')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('status', [
                AppointmentStatus::CONFIRMED
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByDate(\DateTimeInterface $dayParis): array
    {
        $tzParis = new \DateTimeZone('Europe/Paris');
        $tzUtc   = new \DateTimeZone('UTC');

        // bornes Paris
        $startParis = \DateTimeImmutable::createFromInterface($dayParis)
            ->setTime(0, 0, 0, 0)
            ->setTimezone($tzParis);
        $endParis = $startParis->modify('+1 day');

        // convertis à UTC pour la requête (important si la colonne est UTC)
        $startUtc = $startParis->setTimezone($tzUtc);
        $endUtc   = $endParis->setTimezone($tzUtc);

        return $this->createQueryBuilder('a')
            ->andWhere('a.startAt < :endUtc')
            ->andWhere('a.endAt   >= :startUtc')
            ->setParameter('startUtc', $startUtc)
            ->setParameter('endUtc',   $endUtc)
            ->orderBy('a.startAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function hasOverlap(\DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.startAt < :end')
            ->andWhere('a.endAt   > :start')
            ->andWhere('a.status IN (:blocking)')
            ->setParameter('start', $start)
            ->setParameter('end',   $end)
            ->setParameter('blocking', [AppointmentStatus::PENDING, AppointmentStatus::CONFIRMED])
            ->getQuery()->getSingleScalarResult() > 0;
    }

    public function findForCalendarExport(bool $includePending = false, bool $onlyNotSent = true): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.type', 't')->addSelect('t')
            ->join('a.user', 'u')->addSelect('u')
            ->orderBy('a.startAt', 'ASC');

        $statuses = [AppointmentStatus::CONFIRMED];
        if ($includePending) {
            $statuses[] = AppointmentStatus::PENDING;
        }
        $qb->andWhere('a.status IN (:st)')->setParameter('st', $statuses);

        if ($onlyNotSent) {
            $qb->andWhere('a.isSent = :sent')->setParameter('sent', false);
        }

        return $qb->getQuery()->getResult();
    }

    public function markAsSent(array $appointments): void
    {
        if (!$appointments) {
            return;
        }

        foreach ($appointments as $a) {
            $a->setIsSent(true);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Compte les rendez-vous payés par user
     *
     * @param User $user
     * @return integer
     */
    public function countPaidByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.user = :user')
            ->andWhere('a.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', AppointmentStatus::CONFIRMED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Appointment[] Returns an array of Appointment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Appointment
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
