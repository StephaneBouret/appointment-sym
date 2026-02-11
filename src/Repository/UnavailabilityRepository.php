<?php

namespace App\Repository;

use App\Entity\Unavailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Unavailability>
 */
class UnavailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Unavailability::class);
    }

    /**
     * Retourne toutes les indisponibilités (heures) qui touchent la journée donnée
     *
     * @param \DateTimeInterface $date
     * @return array
     */
    public function findForDate(\DateTimeInterface $date): array
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 00:00:00');
        $end   = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 23:59:59');

        return $this->createQueryBuilder('u')
            ->andWhere('u.startAt < :end AND u.endAt > :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('u.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Existe-t-il une indisponibilité "allDay" touchant la date ?
     *
     * @param \DateTimeInterface $date
     * @return boolean
     */
    public function hasAllDay(\DateTimeInterface $date): bool
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 00:00:00');
        $end = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 23:59:59');

        return (bool) $this->createQueryBuilder('u')
            ->andWhere('u.allDay = :allDay')
            ->andWhere('u.startAt < :end AND u.endAt > :start')
            ->setParameter('allDay', true)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Unavailability[] Returns an array of Unavailability objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Unavailability
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
