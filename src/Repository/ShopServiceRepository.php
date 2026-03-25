<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Shop;
use App\Entity\ShopService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopService>
 */
final class ShopServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopService::class);
    }

    /**
     * @return ShopService[]
     */
    public function findByShop(Shop $shop, bool $includeInactive = false): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.shop = :shop')
            ->setParameter('shop', $shop)
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.name', 'ASC');

        if (!$includeInactive) {
            $qb->andWhere('s.isActive = true');
        }

        return $qb->getQuery()->getResult();
    }
}
