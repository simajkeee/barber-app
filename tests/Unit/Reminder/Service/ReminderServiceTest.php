<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reminder\Service;

use App\Common\Exception\ApiException;
use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\User;
use App\Reminder\Dto\ReminderTodayQuery;
use App\Reminder\Entity\ReminderSettings;
use App\Reminder\Service\MessageTemplateResolver;
use App\Reminder\Service\ReminderService;
use App\Reminder\Service\ReminderSettingsService;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(ReminderService::class)]
final class ReminderServiceTest extends TestCase
{
    private ClientRepository&MockObject $clientRepository;
    private ReminderSettingsService&MockObject $settingsService;
    private MessageTemplateResolver $messageResolver;
    private EntityManagerInterface&MockObject $em;
    private ReminderService $sut;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->settingsService = $this->createMock(ReminderSettingsService::class);
        $this->messageResolver = new MessageTemplateResolver();
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->sut = new ReminderService(
            $this->clientRepository,
            $this->settingsService,
            $this->messageResolver,
            $this->em,
        );
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('barber@example.com');
        $user->setFirstName('Nguyen');
        $user->setLastName('Van');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    private function createClient(Shop $shop, string $firstName = 'John', string $lastName = 'Doe'): Client
    {
        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName($firstName);
        $client->setLastName($lastName);
        $client->setPhone('+84901234567');

        return $client;
    }

    private function createSettings(Shop $shop, int $days = 30): ReminderSettings
    {
        $settings = new ReminderSettings();
        $settings->setShop($shop);
        $settings->setDaysSinceLastVisit($days);

        return $settings;
    }

    private function mockCandidates(array $results, int $total = 0): void
    {
        $this->clientRepository->method('countReminderCandidates')->willReturn($total);
        $this->clientRepository->method('findReminderCandidates')->willReturn($results);
        $this->clientRepository->method('encodeReminderCursor')->willReturn('mock-cursor');
    }

    // --- getTodayReminders ---

    #[Test]
    public function testGetTodayRemindersReturnsEmptyListWhenNoClients(): void
    {
        $shop = $this->createShop();
        $settings = $this->createSettings($shop);

        $this->settingsService->method('getSettings')->with($shop)->willReturn($settings);
        $this->mockCandidates([], 0);

        $result = $this->sut->getTodayReminders($shop, new ReminderTodayQuery());

        self::assertSame([], $result['data']);
        self::assertSame(0, $result['meta']['total']);
        self::assertNull($result['meta']['cursor']);
        self::assertSame(30, $result['settings']['daysSinceLastVisit']);
    }

    #[Test]
    public function testGetTodayRemindersReturnsCandidatesWithResolvedMessages(): void
    {
        $shop = $this->createShop();
        $settings = $this->createSettings($shop);

        $client = $this->createClient($shop, 'Nguyễn', 'Văn A');
        $client->setLastVisitAt(new \DateTimeImmutable('-45 days', new \DateTimeZone('Asia/Ho_Chi_Minh')));

        $this->settingsService->method('getSettings')->willReturn($settings);
        $this->mockCandidates([$client], 1);

        $result = $this->sut->getTodayReminders($shop, new ReminderTodayQuery());

        self::assertCount(1, $result['data']);
        $candidate = $result['data'][0];
        self::assertSame((string) $client->getId(), $candidate['clientId']);
        self::assertSame('Nguyễn Văn A', $candidate['clientName']);
        self::assertSame('+84901234567', $candidate['clientPhone']);
        self::assertSame(45, $candidate['daysSinceVisit']);
        self::assertNull($candidate['lastRemindedAt']);
        self::assertStringContainsString('Nguyễn Văn A', $candidate['message']);
        self::assertStringContainsString('Test Shop', $candidate['message']);
        self::assertStringContainsString('45', $candidate['message']);
    }

    #[Test]
    public function testGetTodayRemindersIncludesSettingsInResponse(): void
    {
        $shop = $this->createShop();
        $settings = $this->createSettings($shop, 14);
        $settings->setMessageTemplate('Custom: {client_name}');

        $this->settingsService->method('getSettings')->willReturn($settings);
        $this->mockCandidates([], 0);

        $result = $this->sut->getTodayReminders($shop, new ReminderTodayQuery());

        self::assertSame(14, $result['settings']['daysSinceLastVisit']);
        self::assertSame('Custom: {client_name}', $result['settings']['messageTemplate']);
    }

    #[Test]
    public function testGetTodayRemindersReturnsTotalCount(): void
    {
        $shop = $this->createShop();
        $settings = $this->createSettings($shop);

        $this->settingsService->method('getSettings')->willReturn($settings);
        $this->mockCandidates([], 12);

        $result = $this->sut->getTodayReminders($shop, new ReminderTodayQuery());

        self::assertSame(12, $result['meta']['total']);
    }

    #[Test]
    public function testGetTodayRemindersPassesCursorToRepository(): void
    {
        $shop = $this->createShop();
        $settings = $this->createSettings($shop);
        $cursor = base64_encode(json_encode(['id' => (string) Uuid::v7(), 'lastVisitAt' => '2026-01-01T00:00:00+07:00']));

        $this->settingsService->method('getSettings')->willReturn($settings);
        $this->clientRepository->method('countReminderCandidates')->willReturn(0);
        $this->clientRepository->expects(self::once())
            ->method('findReminderCandidates')
            ->with(
                $shop,
                self::isInstanceOf(\DateTimeImmutable::class),
                self::isInstanceOf(\DateTimeImmutable::class),
                50,
                $cursor,
            )
            ->willReturn([]);

        $result = $this->sut->getTodayReminders($shop, new ReminderTodayQuery(cursor: $cursor));

        self::assertSame([], $result['data']);
    }

    #[Test]
    public function testGetTodayRemindersPaginatesAndSetsCursor(): void
    {
        $shop = $this->createShop();
        $settings = $this->createSettings($shop);

        // Create limit+1 clients to trigger hasMore
        $clients = [];
        for ($i = 0; $i < 51; ++$i) {
            $client = $this->createClient($shop);
            $client->setLastVisitAt(new \DateTimeImmutable('-'.($i + 31).' days'));
            $clients[] = $client;
        }

        $this->settingsService->method('getSettings')->willReturn($settings);
        $this->clientRepository->method('countReminderCandidates')->willReturn(51);
        $this->clientRepository->method('findReminderCandidates')->willReturn($clients);
        $this->clientRepository->expects(self::once())->method('encodeReminderCursor')->willReturn('next-page-cursor');

        $result = $this->sut->getTodayReminders($shop, new ReminderTodayQuery(limit: 50));

        self::assertCount(50, $result['data']);
        self::assertSame('next-page-cursor', $result['meta']['cursor']);
    }

    // --- markReminded ---

    #[Test]
    public function testMarkRemindedSetsLastRemindedAtAndFlushes(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $clientId = $client->getId();

        $this->clientRepository->method('findByShopAndId')->with($shop, $clientId)->willReturn($client);
        $this->em->expects(self::once())->method('flush');

        $result = $this->sut->markReminded($shop, $clientId);

        self::assertSame($client, $result);
        self::assertNotNull($result->getLastRemindedAt());
    }

    #[Test]
    public function testMarkRemindedThrows404WhenClientNotFound(): void
    {
        $shop = $this->createShop();
        $clientId = Uuid::v7();

        $this->clientRepository->method('findByShopAndId')->with($shop, $clientId)->willReturn(null);
        $this->em->expects(self::never())->method('flush');

        try {
            $this->sut->markReminded($shop, $clientId);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(404, $e->statusCode);
            self::assertSame('CLIENT_NOT_FOUND', $e->errorCode);
        }
    }

    #[Test]
    public function testMarkRemindedIsIdempotent(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $client->setLastRemindedAt(new \DateTimeImmutable('-1 day'));

        $this->clientRepository->method('findByShopAndId')->willReturn($client);

        $result = $this->sut->markReminded($shop, $client->getId());

        self::assertNotNull($result->getLastRemindedAt());
    }

    // --- cursor error propagation ---

    #[Test]
    public function testGetTodayRemindersPropagatesInvalidCursorException(): void
    {
        $shop = $this->createShop();
        $settings = $this->createSettings($shop);
        $cursor = 'not-valid-base64!!!';

        $this->settingsService->method('getSettings')->willReturn($settings);
        $this->clientRepository->method('countReminderCandidates')->willReturn(0);
        $this->clientRepository->method('findReminderCandidates')
            ->willThrowException(new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400));

        try {
            $this->sut->getTodayReminders($shop, new ReminderTodayQuery(cursor: $cursor));
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('INVALID_CURSOR', $e->errorCode);
        }
    }

    #[Test]
    public function testGetTodayRemindersPropagatesMissingCursorFieldsException(): void
    {
        $shop = $this->createShop();
        $settings = $this->createSettings($shop);
        $cursor = base64_encode(json_encode(['id' => 'test']));

        $this->settingsService->method('getSettings')->willReturn($settings);
        $this->clientRepository->method('countReminderCandidates')->willReturn(0);
        $this->clientRepository->method('findReminderCandidates')
            ->willThrowException(new ApiException('INVALID_CURSOR', 'Invalid cursor value.', 400));

        try {
            $this->sut->getTodayReminders($shop, new ReminderTodayQuery(cursor: $cursor));
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('INVALID_CURSOR', $e->errorCode);
        }
    }

    // --- serializeCandidate ---

    #[Test]
    public function testSerializeCandidateReturnsExpectedStructure(): void
    {
        $shop = $this->createShop();
        $settings = $this->createSettings($shop);
        $client = $this->createClient($shop);
        $client->setLastVisitAt(new \DateTimeImmutable('-30 days', new \DateTimeZone('Asia/Ho_Chi_Minh')));

        $this->settingsService->method('getSettings')->willReturn($settings);
        $this->mockCandidates([$client], 1);

        $result = $this->sut->getTodayReminders($shop, new ReminderTodayQuery());
        $candidate = $result['data'][0];

        self::assertArrayHasKey('clientId', $candidate);
        self::assertArrayHasKey('clientName', $candidate);
        self::assertArrayHasKey('clientPhone', $candidate);
        self::assertArrayHasKey('daysSinceVisit', $candidate);
        self::assertArrayHasKey('lastVisitAt', $candidate);
        self::assertArrayHasKey('lastRemindedAt', $candidate);
        self::assertArrayHasKey('message', $candidate);
    }
}
