<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Command;

use App\Auth\Enum\UserLocale;
use App\Entity\Shop;
use App\Entity\Subscription;
use App\Entity\User;
use App\Subscription\Command\SendTrialRemindersCommand;
use App\Subscription\Message\SendTrialEndingEmailMessage;
use App\Subscription\Service\SubscriptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(SendTrialRemindersCommand::class)]
final class SendTrialRemindersCommandTest extends TestCase
{
    private SubscriptionService&MockObject $subscriptionService;
    private MessageBusInterface&MockObject $bus;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->subscriptionService = $this->createMock(SubscriptionService::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $command = new SendTrialRemindersCommand($this->subscriptionService, $this->bus, 'https://example.com');
        $this->commandTester = new CommandTester($command);
    }

    #[Test]
    public function testCommandDispatchesReminderForEachExpiring(): void
    {
        $trialEndsAt = new \DateTimeImmutable('+3 days');
        $sub = $this->createSubscriptionWithOwner('john@test.com', 'John', 'Shop A', UserLocale::EN, $trialEndsAt);

        $this->subscriptionService->expects(self::once())
            ->method('sendTrialExpiryReminders')
            ->willReturn([$sub]);

        $dispatched = [];
        $this->bus->method('dispatch')
            ->willReturnCallback(function ($message) use (&$dispatched) {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertCount(1, $dispatched);
        self::assertInstanceOf(SendTrialEndingEmailMessage::class, $dispatched[0]);
        self::assertSame('john@test.com', $dispatched[0]->ownerEmail);
        self::assertSame('John', $dispatched[0]->ownerFirstName);
        self::assertSame('Shop A', $dispatched[0]->shopName);
        self::assertSame($trialEndsAt, $dispatched[0]->trialEndsAtUtc);
        self::assertSame('en', $dispatched[0]->locale);
        self::assertSame('https://example.com/dashboard/subscription', $dispatched[0]->upgradeUrl);
    }

    #[Test]
    public function testCommandOutputsZeroWhenNoExpiring(): void
    {
        $this->subscriptionService->expects(self::once())
            ->method('sendTrialExpiryReminders')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('0', $this->commandTester->getDisplay());
    }

    private function createSubscriptionWithOwner(
        string $email,
        string $firstName,
        string $shopName,
        UserLocale $locale,
        \DateTimeImmutable $trialEndsAt,
    ): Subscription&MockObject {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($email);
        $user->method('getFirstName')->willReturn($firstName);
        $user->method('getLocale')->willReturn($locale);

        $shop = $this->createMock(Shop::class);
        $shop->method('getOwner')->willReturn($user);
        $shop->method('getName')->willReturn($shopName);

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getShop')->willReturn($shop);
        $subscription->method('getTrialEndsAt')->willReturn($trialEndsAt);

        return $subscription;
    }
}
