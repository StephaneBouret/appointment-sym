<?php

namespace App\Repository;

use App\Entity\ScheduleSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleSetting>
 */
class ScheduleSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleSetting::class);
    }

    public function findAllKeyValue(): array
    {
        $qb = $this->createQueryBuilder('s');
        $settings = [];
        foreach ($qb->getQuery()->getResult() as $setting) {
            $settings[$setting->getSettingKey()] = $setting->getValue();
        }
        return $settings;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $row = $this->findOneBy(['setting_key' => $key]);
        return $row?->getValue() ?? $default;
    }

    public function getInt(string $key, int $default): int
    {
        return (int) ($this->get($key, (string) $default));
    }

    public function getBool(string $key, bool $default): bool
    {
        $val = strtolower((string) $this->get($key, $default ? '1' : '0'));
        return in_array($val, ['1', 'true', 'yes', 'on'], true);
    }

    //    /**
    //     * @return ScheduleSetting[] Returns an array of ScheduleSetting objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ScheduleSetting
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
