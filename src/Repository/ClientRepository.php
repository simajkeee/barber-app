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

        if ($filter->search !== '') {
            $escaped = addcslashes($filter->search, '%_');
            $qb->andWhere(
                'LOWER(c.firstName) LIKE LOWER(:search) OR LOWER(c.lastName) LIKE LOWER(:search) OR c.phone LIKE :search'
            )
            ->setParameter('search', '%' . $escaped . '%');
        }

        if ($filter->cursor !== null) {
            $cursorData = $this->decodeCursor($filter->cursor);
            $this->applyCursorCondition($qb, $sortColumn, $direction, $cursorData);
        }

        $qb->orderBy($sortColumn, $direction);
        if ($sortColumn !== 'c.createdAt') {
            $qb->addOrderBy('c.createdAt', 'DESC');
        }
        $qb->addOrderBy('c.id', 'DESC');

        $qb->setMaxResults($filter->limit + 1);

        $results = $qb->getQuery()->getResult();
        $hasMore = count($results) > $filter->limit;

        if ($hasMore) {
            array_pop($results);
        }

        $nextCursor = null;
        if ($hasMore && count($results) > 0) {
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
        if ($decoded === false) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        try {
            $data = json_decode($decoded, true, 3, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        if (!is_array($data) || !isset($data['id'], $data['created_at'])) {
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
        $op = $direction === 'ASC' ? '>' : '<';
        $sortValue = $cursorData['sort_value'] ?? null;

        if ($sortValue === null || $sortColumn === 'c.createdAt') {
            $qb->andWhere("({$sortColumn} {$op} :cursorValue OR ({$sortColumn} = :cursorValue AND c.id < :cursorId))")
                ->setParameter('cursorValue', $sortValue)
                ->setParameter('cursorId', Uuid::fromString($cursorData['id']));
            return;
        }

        $qb->andWhere(
            "({$sortColumn} {$op} :cursorValue OR ({$sortColumn} = :cursorValue AND c.createdAt < :cursorCreated) OR ({$sortColumn} = :cursorValue AND c.createdAt = :cursorCreated AND c.id < :cursorId))"
        )
        ->setParameter('cursorValue', $sortValue)
        ->setParameter('cursorCreated', $cursorData['created_at'])
        ->setParameter('cursorId', Uuid::fromString($cursorData['id']));
    }
}