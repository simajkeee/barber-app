<?php

declare(strict_types=1);

namespace App\Repository;

use App\Appointment\Enum\AppointmentStatus;
use App\Common\Exception\ApiException;
use App\Entity\Appointment;
use App\Entity\Shop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
final class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findByShopAndId(Shop $shop, Uuid $id): ?Appointment
    {
        return $this->findOneBy(['shop' => $shop, 'id' => $id]);
    }

    /**
     * @return Appointment[]
     */
    public function findNonCancelledInRange(Shop $shop, \DateTimeImmutable $start, \DateTimeImmutable $end, ?Uuid $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.shop = :shop')
            ->andWhere('a.startTime < :end')
            ->andWhere('a.endTime > :start')
            ->andWhere('a.status NOT IN (:excludedStatuses)')
            ->setParameter('shop', $shop)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('excludedStatuses', [AppointmentStatus::CANCELLED->value, AppointmentStatus::NO_SHOW->value]);

        if ($excludeId !== null) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Appointment[]
     */
    public function findByShopAndDate(Shop $shop, \DateTimeImmutable $dayStart, \DateTimeImmutable $dayEnd): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.shop = :shop')
            ->andWhere('a.startTime >= :dayStart')
            ->andWhere('a.startTime < :dayEnd')
            ->setParameter('shop', $shop)
            ->setParameter('dayStart', $dayStart)
            ->setParameter('dayEnd', $dayEnd)
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $statuses
     * @return array{appointments: Appointment[], nextCursor: ?string}
     */
    public function findByShopWithFilter(
        Shop $shop,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        array $statuses,
        ?Uuid $clientId,
        ?string $cursor,
        int $limit,
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->where('a.shop = :shop')
            ->setParameter('shop', $shop);

        if ($dateFrom !== null) {
            $qb->andWhere('a.startTime >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $dayEnd = $dateTo->modify('+1 day');
            $qb->andWhere('a.startTime < :dateTo')
                ->setParameter('dateTo', $dayEnd);
        }

        if ($statuses !== []) {
            $qb->andWhere('a.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        if ($clientId !== null) {
            $qb->andWhere('a.client = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($cursor !== null) {
            $cursorData = $this->decodeCursor($cursor);
            $qb->andWhere('(a.startTime < :cursorStart OR (a.startTime = :cursorStart AND a.id < :cursorId))')
                ->setParameter('cursorStart', new \DateTimeImmutable($cursorData['start_time']))
                ->setParameter('cursorId', Uuid::fromString($cursorData['id']));
        }

        $qb->orderBy('a.startTime', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults($limit + 1);

        $results = $qb->getQuery()->getResult();
        $hasMore = count($results) > $limit;

        if ($hasMore) {
            array_pop($results);
        }

        $nextCursor = null;
        if ($hasMore && count($results) > 0) {
            $last = $results[array_key_last($results)];
            $nextCursor = $this->encodeCursor($last);
        }

        return [
            'appointments' => $results,
            'nextCursor' => $nextCursor,
        ];
    }

    /**
     * @return array{date: string, revenue: int, count: int}[]
     */
    public function getRevenueByDay(Shop $shop, \DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array
    {
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $dayEnd = $dateTo->modify('+1 day');

        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                DATE(start_time AT TIME ZONE 'Asia/Ho_Chi_Minh') AS date,
                SUM(price) AS revenue,
                COUNT(*) AS count
            FROM appointments
            WHERE shop_id = :shopId
              AND status = :status
              AND start_time >= :dateFrom
              AND start_time < :dateTo
            GROUP BY DATE(start_time AT TIME ZONE 'Asia/Ho_Chi_Minh')
            ORDER BY date ASC
        ";

        $rows = $conn->fetchAllAssociative($sql, [
            'shopId' => (string) $shop->getId(),
            'status' => AppointmentStatus::COMPLETED->value,
            'dateFrom' => $dateFrom->format('Y-m-d H:i:sP'),
            'dateTo' => $dayEnd->format('Y-m-d H:i:sP'),
        ]);

        return array_map(static fn (array $row) => [
            'date' => $row['date'],
            'revenue' => (int) $row['revenue'],
            'count' => (int) $row['count'],
        ], $rows);
    }

    public function countByShopPhoneAndDateRange(
        Shop $shop,
        string $phone,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
    ): int {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.client', 'c')
            ->where('a.shop = :shop')
            ->andWhere('c.phone = :phone')
            ->andWhere('a.createdAt >= :start')
            ->andWhere('a.createdAt <= :end')
            ->andWhere('a.status != :cancelled')
            ->setParameter('shop', $shop)
            ->setParameter('phone', $phone)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('cancelled', AppointmentStatus::CANCELLED->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function encodeCursor(Appointment $appointment): string
    {
        return base64_encode(json_encode([
            'id' => (string) $appointment->getId(),
            'start_time' => $appointment->getStartTime()->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{id: string, start_time: string}
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

        if (!is_array($data) || !isset($data['id'], $data['start_time'])) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        return $data;
    }
}
