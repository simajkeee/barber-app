<?php

declare(strict_types=1);

namespace App\Repository;

use App\Client\Dto\ClientListFilter;
use App\Common\Exception\ApiException;
use App\Entity\Client;
use App\Entity\Shop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Client>
 */
final class ClientRepository extends ServiceEntityRepository
{
    private const SORT_COLUMN_MAP = [
        'created_at' => 'c.createdAt',
        'last_visit_at' => 'c.lastVisitAt',
        'last_name' => 'c.lastName',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findByShopAndId(Shop $shop, Uuid $id): ?Client
    {
        return $this->findOneBy(['shop' => $shop, 'id' => $id]);
    }

    /**
     * @return Client[]
     */
    public function findReminderCandidates(
        Shop $shop,
        \DateTimeImmutable $threshold,
        \DateTimeImmutable $cooldown,
        int $limit,
        ?string $cursor,
    ): array {
        $qb = $this->buildReminderBaseQuery($shop, $threshold, $cooldown)
            ->orderBy('c.lastVisitAt', 'ASC')
            ->addOrderBy('c.id', 'ASC');

        if (null !== $cursor) {
            $cursorData = $this->decodeReminderCursor($cursor);
            $qb->andWhere('(c.lastVisitAt > :cursorVisit OR (c.lastVisitAt = :cursorVisit AND c.id > :cursorId))')
                ->setParameter('cursorVisit', $cursorData['lastVisitAt'])
                ->setParameter('cursorId', Uuid::fromString($cursorData['id']));
        }

        $qb->setMaxResults($limit + 1);

        return $qb->getQuery()->getResult();
    }

    public function countReminderCandidates(
        Shop $shop,
        \DateTimeImmutable $threshold,
        \DateTimeImmutable $cooldown,
    ): int {
        return (int) $this->buildReminderBaseQuery($shop, $threshold, $cooldown)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function encodeReminderCursor(Client $client): string
    {
        return base64_encode(json_encode([
            'id' => (string) $client->getId(),
            'lastVisitAt' => $client->getLastVisitAt()->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{id: string, lastVisitAt: string}
     */
    public function decodeReminderCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);
        if (false === $decoded) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        try {
            $data = json_decode($decoded, true, 3, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        if (!\is_array($data) || !isset($data['id'], $data['lastVisitAt'])) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        return $data;
    }

    private function buildReminderBaseQuery(
        Shop $shop,
        \DateTimeImmutable $threshold,
        \DateTimeImmutable $cooldown,
    ): \Doctrine\ORM\QueryBuilder {
        return $this->createQueryBuilder('c')
            ->where('c.shop = :shop')
            ->andWhere('c.lastVisitAt IS NOT NULL')
            ->andWhere('c.lastVisitAt <= :threshold')
            ->andWhere('c.lastRemindedAt IS NULL OR c.lastRemindedAt <= :cooldown')
            ->setParameter('shop', $shop)
            ->setParameter('threshold', $threshold)
            ->setParameter('cooldown', $cooldown);
    }

    public function findByShopAndPhone(Shop $shop, string $phone): ?Client
    {
        return $this->findOneBy(['shop' => $shop, 'phone' => $phone]);
    }

    /**
     * @return array{clients: Client[], nextCursor: ?string, hasMore: bool}
     */
    public function findByShopWithFilter(Shop $shop, ClientListFilter $filter): array
    {
        $sortColumn = self::SORT_COLUMN_MAP[$filter->sort];
        $direction = strtoupper($filter->direction);

        $qb = $this->createQueryBuilder('c')
            ->where('c.shop = :shop')
            ->setParameter('shop', $shop);

        if ('' !== $filter->search) {
            $escaped = addcslashes($filter->search, '%_');
            $qb->andWhere(
                'LOWER(c.firstName) LIKE LOWER(:search) OR LOWER(c.lastName) LIKE LOWER(:search) OR c.phone LIKE :search',
            )
            ->setParameter('search', '%'.$escaped.'%');
        }

        if (null !== $filter->cursor) {
            $cursorData = $this->decodeCursor($filter->cursor);
            $this->applyCursorCondition($qb, $sortColumn, $direction, $cursorData);
        }

        $qb->orderBy($sortColumn, $direction);
        if ('c.createdAt' !== $sortColumn) {
            $qb->addOrderBy('c.createdAt', 'DESC');
        }
        $qb->addOrderBy('c.id', 'DESC');

        $qb->setMaxResults($filter->limit + 1);

        $results = $qb->getQuery()->getResult();
        $hasMore = \count($results) > $filter->limit;

        if ($hasMore) {
            array_pop($results);
        }

        $nextCursor = null;
        if ($hasMore && \count($results) > 0) {
            $lastClient = $results[array_key_last($results)];
            $nextCursor = $this->encodeCursor($lastClient, $filter->sort);
        }

        return [
            'clients' => $results,
            'nextCursor' => $nextCursor,
            'hasMore' => $hasMore,
        ];
    }

    private function encodeCursor(Client $client, string $sort): string
    {
        $data = [
            'id' => (string) $client->getId(),
            'created_at' => $client->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];

        $data['sort_value'] = match ($sort) {
            'last_visit_at' => $client->getLastVisitAt()?->format(\DateTimeInterface::ATOM),
            'last_name' => $client->getLastName(),
            default => $client->getCreatedAt()->format(\DateTimeInterface::ATOM),
        };

        return base64_encode(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{id: string, created_at: string, sort_value: ?string}
     */
    private function decodeCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);
        if (false === $decoded) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        try {
            $data = json_decode($decoded, true, 3, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        if (!\is_array($data) || !isset($data['id'], $data['created_at'])) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        return $data;
    }

    /**
     * @param array{id: string, created_at: string, sort_value: ?string} $cursorData
     */
    private function applyCursorCondition(
        \Doctrine\ORM\QueryBuilder $qb,
        string $sortColumn,
        string $direction,
        array $cursorData,
    ): void {
        $op = 'ASC' === $direction ? '>' : '<';
        $sortValue = $cursorData['sort_value'] ?? null;

        if (null === $sortValue || 'c.createdAt' === $sortColumn) {
            $qb->andWhere("({$sortColumn} {$op} :cursorValue OR ({$sortColumn} = :cursorValue AND c.id < :cursorId))")
                ->setParameter('cursorValue', $sortValue)
                ->setParameter('cursorId', Uuid::fromString($cursorData['id']));

            return;
        }

        $qb->andWhere(
            "({$sortColumn} {$op} :cursorValue OR ({$sortColumn} = :cursorValue AND c.createdAt < :cursorCreated) OR ({$sortColumn} = :cursorValue AND c.createdAt = :cursorCreated AND c.id < :cursorId))",
        )
        ->setParameter('cursorValue', $sortValue)
        ->setParameter('cursorCreated', $cursorData['created_at'])
        ->setParameter('cursorId', Uuid::fromString($cursorData['id']));
    }
}
