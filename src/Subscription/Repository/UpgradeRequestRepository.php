<?php

declare(strict_types=1);

namespace App\Subscription\Repository;

use App\Entity\User;
use App\Subscription\Entity\UpgradeRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UpgradeRequest>
 */
final class UpgradeRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UpgradeRequest::class);
    }

    public function findRecentByUser(User $user, int $days): ?UpgradeRequest
    {
        $since = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('u')
            ->where('u.user = :user')
            ->andWhere('u.createdAt >= :since')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('since', $since, 'datetimetz_immutable')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(UpgradeRequest $upgradeRequest): void
    {
        $this->getEntityManager()->persist($upgradeRequest);
        $this->getEntityManager()->flush();
    }
}
