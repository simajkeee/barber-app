<?php

declare(strict_types=1);

namespace App\Reminder\Repository;

use App\Entity\Client;
use App\Reminder\Entity\ReminderOptOutToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReminderOptOutToken>
 */
final class ReminderOptOutTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReminderOptOutToken::class);
    }

    public function findByClient(Client $client): ?ReminderOptOutToken
    {
        return $this->findOneBy(['client' => $client]);
    }

    public function findByToken(string $token): ?ReminderOptOutToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * @param Client[] $clients
     *
     * @return array<string, ReminderOptOutToken> keyed by client ID
     */
    public function findByClients(array $clients): array
    {
        if ([] === $clients) {
            return [];
        }

        $tokens = $this->createQueryBuilder('t')
            ->where('t.client IN (:clients)')
            ->setParameter('clients', $clients)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($tokens as $token) {
            $map[(string) $token->getClient()->getId()] = $token;
        }

        return $map;
    }
}
