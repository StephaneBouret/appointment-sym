<?php

namespace App\Repository;

use App\Doctrine\Type\UtcDateTimeImmutableType;
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
     * @return list<Unavailability>
     */
    public function findForDate(\DateTimeInterface $date): array
    {
        [$start, $end] = $this->utcDayBounds($date);

        return $this->createQueryBuilder('u')
            ->andWhere('u.startAt < :end AND u.endAt > :start')
            ->setParameter('start', $start, UtcDateTimeImmutableType::NAME)
            ->setParameter('end', $end, UtcDateTimeImmutableType::NAME)
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
        [$start, $end] = $this->utcDayBounds($date);

        return (bool) $this->createQueryBuilder('u')
            ->andWhere('u.allDay = :allDay')
            ->andWhere('u.startAt < :end AND u.endAt > :start')
            ->setParameter('allDay', true)
            ->setParameter('start', $start, UtcDateTimeImmutableType::NAME)
            ->setParameter('end', $end, UtcDateTimeImmutableType::NAME)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return array{\DateTimeImmutable, \DateTimeImmutable} */
    private function utcDayBounds(\DateTimeInterface $date): array
    {
        // Callers provide a civil date: retain its Y-m-d component, interpreted in Paris.
        $startParis = new \DateTimeImmutable($date->format('Y-m-d').' 00:00:00', new \DateTimeZone('Europe/Paris'));
        $endParis = $startParis->modify('+1 day');
        $utc = new \DateTimeZone('UTC');

        return [$startParis->setTimezone($utc), $endParis->setTimezone($utc)];
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
