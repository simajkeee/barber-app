<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Shop;
use App\Entity\WorkSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkSchedule>
 */
final class WorkScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkSchedule::class);
    }

    /**
     * @return WorkSchedule[]
     */
    public function findByShop(Shop $shop): array
    {
        return $this->findBy(['shop' => $shop]);
    }

    public function findByShopAndDay(Shop $shop, \App\Shop\Enum\DayOfWeek $day): ?WorkSchedule
    {
        return $this->findOneBy(['shop' => $shop, 'dayOfWeek' => $day]);
    }

    public function deleteByShop(Shop $shop): void
    {
        $this->createQueryBuilder('ws')
            ->delete()
            ->where('ws.shop = :shop')
            ->setParameter('shop', $shop)
            ->getQuery()
            ->execute();
    }
}
