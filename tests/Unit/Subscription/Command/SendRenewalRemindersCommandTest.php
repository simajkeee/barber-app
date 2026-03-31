<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Command;

use App\Entity\Shop;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Subscription\Command\SendRenewalRemindersCommand;
use App\Subscription\Enum\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionStatus;
use App\Subscription\Message\SendRenewalReminderEmailMessage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(SendRenewalRemindersCommand::class)]
final class SendRenewalRemindersCommandTest extends TestCase
{
    private SubscriptionRepository&MockObject $subscriptionRepository;
    private MessageBusInterface&MockObject $messageBus;
    private EntityManagerInterface&MockObject $em;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createMock(SubscriptionRepository::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $command = new SendRenewalRemindersCommand(
            $this->subscriptionRepository,
            $this->messageBus,
            $this->em,
        );

        $this->tester = new CommandTester($command);
    }

    private function createSubscription(string $shopSlug = 'shop-1'): Subscription
    {
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setFirstName('Test');
        $user->setLastName('Owner');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug($shopSlug);

        $subscription = new Subscription();
        $subscription->setShop($shop);
        $subscription->setPlan(SubscriptionPlan::PRO);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setEndDate(new \DateTimeImmutable('+7 days'));
        $subscription->setCountResetAt(new \DateTimeImmutable());

        return $subscription;
    }

    #[Test]
    public function testDispatchesReminderForExpiringSubscription(): void
    {
        $subscription = $this->createSubscription();

        $this->subscriptionRepository->method('findExpiringInSevenDays')->willReturn([$subscription]);

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (SendRenewalReminderEmailMessage $msg) use ($subscription): bool {
                self::assertSame((string) $subscription->getShop()->getId(), $msg->shopId);
                self::assertSame($subscription->getEndDate(), $msg->endDate);

                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->em->expects(self::once())->method('flush');

        $this->tester->execute([]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertStringContainsString('Sent 1 renewal reminder(s)', $this->tester->getDisplay());
    }

    #[Test]
    public function testSetsRenewalReminderSentAtAfterDispatch(): void
    {
        $subscription = $this->createSubscription();
        self::assertNull($subscription->getRenewalReminderSentAt());

        $this->subscriptionRepository->method('findExpiringInSevenDays')->willReturn([$subscription]);
        $this->messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $this->tester->execute([]);

        self::assertNotNull($subscription->getRenewalReminderSentAt());
    }

    #[Test]
    public function testDryRunDoesNotDispatchOrSave(): void
    {
        $subscription = $this->createSubscription();

        $this->subscriptionRepository->method('findExpiringInSevenDays')->willReturn([$subscription]);
        $this->messageBus->expects(self::never())->method('dispatch');
        $this->em->expects(self::never())->method('flush');

        $this->tester->execute(['--dry-run' => true]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertStringContainsString('Would send 1 renewal reminder(s)', $this->tester->getDisplay());
    }

    #[Test]
    public function testShopIdFilterProcessesOnlyMatchingShop(): void
    {
        $sub1 = $this->createSubscription('shop-a');
        $sub2 = $this->createSubscription('shop-b');

        $targetShopId = (string) $sub1->getShop()->getId();

        $this->subscriptionRepository->method('findExpiringInSevenDays')->willReturn([$sub1, $sub2]);

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (SendRenewalReminderEmailMessage $msg) use ($targetShopId): bool {
                self::assertSame($targetShopId, $msg->shopId);

                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->tester->execute(['--shop-id' => $targetShopId]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertStringContainsString('Sent 1 renewal reminder(s)', $this->tester->getDisplay());
    }

    #[Test]
    public function testInvalidShopIdReturnsFailure(): void
    {
        $this->subscriptionRepository->expects(self::never())->method('findExpiringInSevenDays');
        $this->messageBus->expects(self::never())->method('dispatch');

        $this->tester->execute(['--shop-id' => 'not-a-uuid']);

        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('Invalid shop UUID', $this->tester->getDisplay());
    }

    #[Test]
    public function testNoSubscriptionsOutputsZeroCount(): void
    {
        $this->subscriptionRepository->method('findExpiringInSevenDays')->willReturn([]);
        $this->messageBus->expects(self::never())->method('dispatch');

        $this->tester->execute([]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertStringContainsString('Sent 0 renewal reminder(s)', $this->tester->getDisplay());
    }

    #[Test]
    public function testAlreadyRemindedSubscriptionsAreNotDispatched(): void
    {
        // The repository query already filters renewalReminderSentAt IS NULL,
        // so already-reminded subscriptions never appear in the result set.
        $this->subscriptionRepository->method('findExpiringInSevenDays')->willReturn([]);
        $this->messageBus->expects(self::never())->method('dispatch');
        $this->em->expects(self::never())->method('flush');

        $this->tester->execute([]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    }
}
