<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PasswordResetToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
final class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function findByTokenHash(string $tokenHash): ?PasswordResetToken
    {
        return $this->createQueryBuilder('prt')
            ->join('prt.user', 'u')
            ->addSelect('u')
            ->where('prt.tokenHash = :hash')
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function invalidateUnusedTokensForUser(\Symfony\Component\Uid\Uuid $userId): void
    {
        $this->createQueryBuilder('prt')
            ->update()
            ->set('prt.usedAt', ':now')
            ->where('prt.user = :userId')
            ->andWhere('prt.usedAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('userId', $userId, 'uuid')
            ->getQuery()
            ->execute();
    }
}
