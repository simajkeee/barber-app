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
    public function getTodayReminders(Shop $shop, ReminderTodayQuery $query, string $locale = 'vi'): array
    {
        $settings = $this->reminderSettingsService->getSettings($shop, $locale);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $threshold = new \DateTimeImmutable(\sprintf('-%d days', $settings->getDaysSinceLastVisit()), $tz);
        $cooldown = new \DateTimeImmutable(\sprintf('-%d days', self::COOLDOWN_DAYS), $tz);

        $total = $this->clientRepository->countReminderCandidates($shop, $threshold, $cooldown);
        $results = $this->clientRepository->findReminderCandidates($shop, $threshold, $cooldown, $query->limit, $query->cursor);

        $hasMore = \count($results) > $query->limit;
        if ($hasMore) {
            array_pop($results);
        }

        $nextCursor = null;
        if ($hasMore && \count($results) > 0) {
            $nextCursor = $this->clientRepository->encodeReminderCursor($results[array_key_last($results)]);
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

    /**
     * @return array{candidates: list<array{client: Client, candidate: ReminderCandidate}>, hasMore: bool, cursor: ?string}
     */
    public function getEmailReminderCandidates(Shop $shop, string $locale, int $limit, ?string $cursor): array
    {
        $settings = $this->reminderSettingsService->getSettings($shop, $locale);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $threshold = new \DateTimeImmutable(\sprintf('-%d days', $settings->getDaysSinceLastVisit()), $tz);
        $cooldown = new \DateTimeImmutable(\sprintf('-%d days', self::COOLDOWN_DAYS), $tz);
        $now = new \DateTimeImmutable('now', $tz);

        $results = $this->clientRepository->findEmailReminderCandidates($shop, $threshold, $cooldown, $limit, $cursor);

        $hasMore = \count($results) > $limit;
        if ($hasMore) {
            array_pop($results);
        }

        $nextCursor = null;
        if ($hasMore && \count($results) > 0) {
            $nextCursor = $this->clientRepository->encodeReminderCursor($results[array_key_last($results)]);
        }

        $candidates = [];
        foreach ($results as $client) {
            $candidates[] = [
                'client' => $client,
                'candidate' => $this->buildCandidate($client, $settings->getMessageTemplate(), $shop->getName(), $now),
            ];
        }

        return [
            'candidates' => $candidates,
            'hasMore' => $hasMore,
            'cursor' => $nextCursor,
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
        $clientName = $client->getFirstName().' '.$client->getLastName();
        $daysSinceVisit = (int) $now->diff($client->getLastVisitAt())->days;

        $message = $this->messageTemplateResolver->resolve($template, [
            'client_name' => $clientName,
            'shop_name' => $shopName,
            'days_since_visit' => (string) $daysSinceVisit,
            'client_phone' => $client->getPhone(),
        ]);

        return new ReminderCandidate(
            clientId: $client->getId(),
            clientName: $clientName,
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
}
