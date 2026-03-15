<?php

declare(strict_types=1);

namespace App\Reminder\Service;

use App\Common\Exception\ApiException;
use App\Entity\Client;
use App\Entity\Shop;
use App\Reminder\Dto\ReminderCandidate;
use App\Reminder\Dto\ReminderTodayQuery;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class ReminderService
{
    private const COOLDOWN_DAYS = 7;

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly ReminderSettingsService $reminderSettingsService,
        private readonly MessageTemplateResolver $messageTemplateResolver,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array{data: array<array<string, mixed>>, meta: array{total: int, cursor: ?string}, settings: array<string, mixed>}
     */
    public function getTodayReminders(Shop $shop, ReminderTodayQuery $query): array
    {
        $settings = $this->reminderSettingsService->getSettings($shop);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $threshold = new \DateTimeImmutable(\sprintf('-%d days', $settings->getDaysSinceLastVisit()), $tz);
        $cooldown = new \DateTimeImmutable(\sprintf('-%d days', self::COOLDOWN_DAYS), $tz);

        $qb = $this->clientRepository->createQueryBuilder('c')
            ->where('c.shop = :shop')
            ->andWhere('c.lastVisitAt IS NOT NULL')
            ->andWhere('c.lastVisitAt <= :threshold')
            ->andWhere('c.lastRemindedAt IS NULL OR c.lastRemindedAt <= :cooldown')
            ->setParameter('shop', $shop)
            ->setParameter('threshold', $threshold)
            ->setParameter('cooldown', $cooldown)
            ->orderBy('c.lastVisitAt', 'ASC')
            ->addOrderBy('c.id', 'ASC');

        if (null !== $query->cursor) {
            $cursorData = $this->decodeCursor($query->cursor);
            $qb->andWhere(
                '(c.lastVisitAt > :cursorVisit OR (c.lastVisitAt = :cursorVisit AND c.id > :cursorId))',
            )
            ->setParameter('cursorVisit', $cursorData['lastVisitAt'])
            ->setParameter('cursorId', Uuid::fromString($cursorData['id']));
        }

        // Get total count (without pagination)
        $countQb = $this->clientRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.shop = :shop')
            ->andWhere('c.lastVisitAt IS NOT NULL')
            ->andWhere('c.lastVisitAt <= :threshold')
            ->andWhere('c.lastRemindedAt IS NULL OR c.lastRemindedAt <= :cooldown')
            ->setParameter('shop', $shop)
            ->setParameter('threshold', $threshold)
            ->setParameter('cooldown', $cooldown);

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->setMaxResults($query->limit + 1);
        /** @var Client[] $results */
        $results = $qb->getQuery()->getResult();

        $hasMore = \count($results) > $query->limit;
        if ($hasMore) {
            array_pop($results);
        }

        $nextCursor = null;
        if ($hasMore && \count($results) > 0) {
            $lastClient = $results[array_key_last($results)];
            $nextCursor = $this->encodeCursor($lastClient);
        }

        $now = new \DateTimeImmutable('now', $tz);
        $candidates = array_map(
            fn (Client $client) => $this->buildCandidate($client, $settings->getMessageTemplate(), $shop->getName(), $now),
            $results,
        );

        return [
            'data' => array_map(self::serializeCandidate(...), $candidates),
            'meta' => [
                'total' => $total,
                'cursor' => $nextCursor,
            ],
            'settings' => ReminderSettingsService::serializeSettings($settings),
        ];
    }

    public function markReminded(Shop $shop, Uuid $clientId): Client
    {
        $client = $this->clientRepository->findByShopAndId($shop, $clientId);
        if (null === $client) {
            throw new ApiException('CLIENT_NOT_FOUND', 'Client not found.', 404);
        }

        $client->setLastRemindedAt(new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh')));
        $this->em->flush();

        return $client;
    }

    private function buildCandidate(
        Client $client,
        string $template,
        string $shopName,
        \DateTimeImmutable $now,
    ): ReminderCandidate {
        $daysSinceVisit = (int) $now->diff($client->getLastVisitAt())->days;

        $message = $this->messageTemplateResolver->resolve($template, [
            'client_name' => $client->getFirstName().' '.$client->getLastName(),
            'shop_name' => $shopName,
            'days_since_visit' => (string) $daysSinceVisit,
            'client_phone' => $client->getPhone(),
        ]);

        return new ReminderCandidate(
            clientId: $client->getId(),
            clientName: $client->getFirstName().' '.$client->getLastName(),
            clientPhone: $client->getPhone(),
            daysSinceVisit: $daysSinceVisit,
            lastVisitAt: $client->getLastVisitAt(),
            lastRemindedAt: $client->getLastRemindedAt(),
            message: $message,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeCandidate(ReminderCandidate $candidate): array
    {
        return [
            'clientId' => (string) $candidate->clientId,
            'clientName' => $candidate->clientName,
            'clientPhone' => $candidate->clientPhone,
            'daysSinceVisit' => $candidate->daysSinceVisit,
            'lastVisitAt' => $candidate->lastVisitAt->format(\DateTimeInterface::ATOM),
            'lastRemindedAt' => $candidate->lastRemindedAt?->format(\DateTimeInterface::ATOM),
            'message' => $candidate->message,
        ];
    }

    private function encodeCursor(Client $client): string
    {
        $data = [
            'id' => (string) $client->getId(),
            'lastVisitAt' => $client->getLastVisitAt()->format(\DateTimeInterface::ATOM),
        ];

        return base64_encode(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{id: string, lastVisitAt: string}
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

        if (!\is_array($data) || !isset($data['id'], $data['lastVisitAt'])) {
            throw new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400);
        }

        return $data;
    }
}
