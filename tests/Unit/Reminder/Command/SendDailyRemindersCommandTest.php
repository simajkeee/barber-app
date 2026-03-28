<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reminder\Command;

use App\Auth\Enum\UserLocale;
use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\User;
use App\Reminder\Command\SendDailyRemindersCommand;
use App\Reminder\Dto\ReminderCandidate;
use App\Reminder\Entity\ReminderOptOutToken;
use App\Reminder\Message\SendReminderEmailMessage;
use App\Reminder\Repository\ReminderOptOutTokenRepository;
use App\Reminder\Service\ReminderService;
use App\Reminder\Service\ReminderSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(SendDailyRemindersCommand::class)]
final class SendDailyRemindersCommandTest extends TestCase
{
    private ReminderService&MockObject $reminderService;
    private ReminderSettingsService&MockObject $settingsService;
    private ReminderOptOutTokenRepository&MockObject $optOutTokenRepo;
    private MessageBusInterface&MockObject $messageBus;
    private EntityManagerInterface&MockObject $em;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->reminderService = $this->createMock(ReminderService::class);
        $this->settingsService = $this->createMock(ReminderSettingsService::class);
        $this->optOutTokenRepo = $this->createMock(ReminderOptOutTokenRepository::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $command = new SendDailyRemindersCommand(
            $this->reminderService,
            $this->settingsService,
            $this->optOutTokenRepo,
            $this->messageBus,
            $this->em,
            new NullLogger(),
        );

        $this->tester = new CommandTester($command);
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('barber@example.com');
        $user->setFirstName('Nguyen');
        $user->setLastName('Van');
        $user->setLocale(UserLocale::VI);

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    private function createClientWithEmail(Shop $shop, string $email = 'client@example.com'): Client
    {
        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName('John');
        $client->setLastName('Doe');
        $client->setPhone('+84901234567');
        $client->setEmail($email);
        $client->setLastVisitAt(new \DateTimeImmutable('-45 days', new \DateTimeZone('Asia/Ho_Chi_Minh')));

        return $client;
    }

    private function mockTokensForClients(Client ...$clients): void
    {
        $map = [];
        foreach ($clients as $client) {
            $token = new ReminderOptOutToken();
            $token->setClient($client);
            $map[(string) $client->getId()] = $token;
        }

        $this->optOutTokenRepo->method('findByClients')->willReturn($map);
    }

    #[Test]
    public function testNoShopsReturnsSuccess(): void
    {
        $this->settingsService->method('findShopsWithAutomatedEmail')->willReturn([]);

        $this->tester->execute([]);

        self::assertSame(0, $this->tester->getStatusCode());
        self::assertStringContainsString('No shops found', $this->tester->getDisplay());
    }

    #[Test]
    public function testDispatchesEmailForEligibleClient(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientWithEmail($shop);

        $this->settingsService->method('findShopsWithAutomatedEmail')->willReturn([$shop]);
        $this->mockTokensForClients($client);

        $candidate = new ReminderCandidate(
            clientId: $client->getId(),
            clientName: 'John Doe',
            clientPhone: '+84901234567',
            daysSinceVisit: 45,
            lastVisitAt: $client->getLastVisitAt(),
            lastRemindedAt: null,
            message: 'Hello John, time for a haircut!',
        );

        $this->reminderService->method('getEmailReminderCandidates')
            ->willReturn([
                'candidates' => [['client' => $client, 'candidate' => $candidate]],
                'hasMore' => false,
                'cursor' => null,
            ]);

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(SendReminderEmailMessage::class))
            ->willReturn(new Envelope(new \stdClass()));

        $this->em->expects(self::once())->method('flush');

        $this->tester->execute([]);

        self::assertSame(0, $this->tester->getStatusCode());
        self::assertStringContainsString('Dispatched 1', $this->tester->getDisplay());
        self::assertNotNull($client->getLastRemindedAt());
    }

    #[Test]
    public function testDryRunDoesNotDispatchOrUpdateClient(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientWithEmail($shop);

        $this->settingsService->method('findShopsWithAutomatedEmail')->willReturn([$shop]);
        $this->mockTokensForClients($client);

        $candidate = new ReminderCandidate(
            clientId: $client->getId(),
            clientName: 'John Doe',
            clientPhone: '+84901234567',
            daysSinceVisit: 45,
            lastVisitAt: $client->getLastVisitAt(),
            lastRemindedAt: null,
            message: 'Hello John!',
        );

        $this->reminderService->method('getEmailReminderCandidates')
            ->willReturn([
                'candidates' => [['client' => $client, 'candidate' => $candidate]],
                'hasMore' => false,
                'cursor' => null,
            ]);

        $this->messageBus->expects(self::never())->method('dispatch');
        $this->em->expects(self::never())->method('flush');

        $this->tester->execute(['--dry-run' => true]);

        self::assertSame(0, $this->tester->getStatusCode());
        self::assertStringContainsString('Would dispatch 1', $this->tester->getDisplay());
        self::assertNull($client->getLastRemindedAt());
    }

    #[Test]
    public function testCreatesOptOutTokenWhenNoneExists(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientWithEmail($shop);

        $this->settingsService->method('findShopsWithAutomatedEmail')->willReturn([$shop]);
        // Return empty map — no existing tokens
        $this->optOutTokenRepo->method('findByClients')->willReturn([]);

        $candidate = new ReminderCandidate(
            clientId: $client->getId(),
            clientName: 'John Doe',
            clientPhone: '+84901234567',
            daysSinceVisit: 45,
            lastVisitAt: $client->getLastVisitAt(),
            lastRemindedAt: null,
            message: 'Hello!',
        );

        $this->reminderService->method('getEmailReminderCandidates')
            ->willReturn([
                'candidates' => [['client' => $client, 'candidate' => $candidate]],
                'hasMore' => false,
                'cursor' => null,
            ]);

        $this->messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $this->em->expects(self::once())->method('persist')
            ->with(self::isInstanceOf(ReminderOptOutToken::class));
        // Single batch flush (tokens are persisted but flushed with the batch)
        $this->em->expects(self::once())->method('flush');

        $this->tester->execute([]);

        self::assertSame(0, $this->tester->getStatusCode());
    }

    #[Test]
    public function testPaginatesThroughAllCandidates(): void
    {
        $shop = $this->createShop();
        $client1 = $this->createClientWithEmail($shop, 'c1@example.com');
        $client2 = $this->createClientWithEmail($shop, 'c2@example.com');

        $this->mockTokensForClients($client1, $client2);

        $candidate1 = new ReminderCandidate(
            clientId: $client1->getId(), clientName: 'C1', clientPhone: '+1',
            daysSinceVisit: 45, lastVisitAt: $client1->getLastVisitAt(),
            lastRemindedAt: null, message: 'Msg1',
        );
        $candidate2 = new ReminderCandidate(
            clientId: $client2->getId(), clientName: 'C2', clientPhone: '+2',
            daysSinceVisit: 45, lastVisitAt: $client2->getLastVisitAt(),
            lastRemindedAt: null, message: 'Msg2',
        );

        $this->settingsService->method('findShopsWithAutomatedEmail')->willReturn([$shop]);

        $callCount = 0;
        $this->reminderService->method('getEmailReminderCandidates')
            ->willReturnCallback(function () use (&$callCount, $client1, $client2, $candidate1, $candidate2) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'candidates' => [['client' => $client1, 'candidate' => $candidate1]],
                        'hasMore' => true,
                        'cursor' => 'page2',
                    ];
                }

                return [
                    'candidates' => [['client' => $client2, 'candidate' => $candidate2]],
                    'hasMore' => false,
                    'cursor' => null,
                ];
            });

        $this->messageBus->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->tester->execute([]);

        self::assertSame(0, $this->tester->getStatusCode());
        self::assertStringContainsString('Dispatched 2', $this->tester->getDisplay());
    }

    #[Test]
    public function testShopIdOptionFiltersToSingleShop(): void
    {
        $shop = $this->createShop();
        $shopId = (string) $shop->getId();

        $this->settingsService->expects(self::once())
            ->method('findShopsWithAutomatedEmail')
            ->with(self::callback(fn ($uuid) => (string) $uuid === $shopId))
            ->willReturn([]);

        $this->tester->execute(['--shop-id' => $shopId]);

        self::assertSame(0, $this->tester->getStatusCode());
    }
}
