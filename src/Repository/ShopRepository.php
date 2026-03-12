<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Shop;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Shop>
 */
final class ShopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shop::class);
    }

    public function findByOwner(User $user): ?Shop
    {
        return $this->findOneBy(['owner' => $user]);
    }

    public function findBySlug(string $slug): ?Shop
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}