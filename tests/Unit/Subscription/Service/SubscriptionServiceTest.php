<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Service;

use App\Entity\Shop;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Subscription\Enum\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionStatus;
use App\Subscription\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SubscriptionService::class)]
final class SubscriptionServiceTest extends TestCase
{
    private SubscriptionRepository&MockObject $subscriptionRepository;
    private EntityManagerInterface&MockObject $em;
    private SubscriptionService $sut;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createMock(SubscriptionRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->sut = new SubscriptionService(
            $this->subscriptionRepository,
            $this->em,
        );
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    private function createSubscription(
        Shop $shop,
        SubscriptionPlan $plan = SubscriptionPlan::FREE,
        SubscriptionStatus $status = SubscriptionStatus::ACTIVE,
        int $count = 0,
        ?\DateTimeImmutable $endDate = null,
    ): Subscription {
        $subscription = new Subscription();
        $subscription->setShop($shop);
        $subscription->setPlan($plan);
        $subscription->setStatus($status);
        $subscription->setStartDate(new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh')));
        $subscription->setEndDate($endDate);
        $subscription->setMonthlyAppointmentCount($count);
        $subscription->setCountResetAt(new \DateTimeImmutable('first day of this month midnight', new \DateTimeZone('Asia/Ho_Chi_Minh')));

        return $subscription;
    }

    // --- createTrialForShop ---

    #[Test]
    public function testCreateTrialForShopSetsPlanProWithTrialEndDate(): void
    {
        $shop = $this->createShop();

        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $before = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $subscription = $this->sut->createTrialForShop($shop);
        $after = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        self::assertSame(SubscriptionPlan::PRO, $subscription->getPlan());
        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
        self::assertNull($subscription->getEndDate());
        self::assertSame(0, $subscription->getMonthlyAppointmentCount());
        self::assertSame($shop, $subscription->getShop());
        self::assertNotNull($subscription->getTrialEndsAt());
        self::assertGreaterThanOrEqual(
            $before->modify('+30 days')->getTimestamp(),
            $subscription->getTrialEndsAt()->getTimestamp()
        );
        self::assertLessThanOrEqual(
            $after->modify('+30 days')->getTimestamp(),
            $subscription->getTrialEndsAt()->getTimestamp()
        );
    }

    #[Test]
    public function testGetByShopCreatesFreeSubscriptionAsFallbackForExistingShops(): void
    {
        $shop = $this->createShop();

        $this->subscriptionRepository->method('findByShop')->with($shop)->willReturn(null);
        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $subscription = $this->sut->getByShop($shop);

        self::assertSame(SubscriptionPlan::FREE, $subscription->getPlan());
        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
        self::assertNull($subscription->getTrialEndsAt());
    }

    // --- activate ---

    #[Test]
    public function testActivateSetsPlanToProAndEndDateCorrectly(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);

        $this->subscriptionRepository->method('findByShop')->with($shop)->willReturn($subscription);

        $before = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $result = $this->sut->activate($shop, 30);
        $after = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        self::assertSame(SubscriptionPlan::PRO, $result->getPlan());
        self::assertSame(SubscriptionStatus::ACTIVE, $result->getStatus());
        self::assertNotNull($result->getEndDate());
        // startDate must be reset to ~now (BR8)
        self::assertGreaterThanOrEqual($before->getTimestamp(), $result->getStartDate()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $result->getStartDate()->getTimestamp());
    }

    #[Test]
    public function testActivateOnExpiredProResetsFromNow(): void
    {
        $shop = $this->createShop();
        $pastEndDate = new \DateTimeImmutable('-5 days', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $subscription = $this->createSubscription($shop, SubscriptionPlan::PRO, SubscriptionStatus::EXPIRED, endDate: $pastEndDate);

        $this->subscriptionRepository->method('findByShop')->with($shop)->willReturn($subscription);

        $beforeActivation = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $result = $this->sut->activate($shop, 30);

        self::assertNotNull($result->getEndDate());
        // endDate should be ~30 days from now, not from the expired endDate
        $daysDiff = (int) $beforeActivation->diff($result->getEndDate())->days;
        self::assertGreaterThanOrEqual(29, $daysDiff);
        self::assertLessThanOrEqual(31, $daysDiff);
    }

    #[Test]
    public function testActivateOnActiveProExtendsFromCurrentEndDate(): void
    {
        $shop = $this->createShop();
        $futureEndDate = new \DateTimeImmutable('+10 days', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $subscription = $this->createSubscription($shop, SubscriptionPlan::PRO, SubscriptionStatus::ACTIVE, endDate: $futureEndDate);

        $this->subscriptionRepository->method('findByShop')->with($shop)->willReturn($subscription);

        $result = $this->sut->activate($shop, 30);

        self::assertNotNull($result->getEndDate());
        // Should be ~40 days from now (10 remaining + 30 new)
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $daysDiff = (int) $now->diff($result->getEndDate())->days;
        self::assertGreaterThanOrEqual(39, $daysDiff);
        self::assertLessThanOrEqual(41, $daysDiff);
    }

    // --- canCreateAppointment ---

    #[Test]
    public function testCanCreateAppointmentReturnsTrueForProRegardlessOfCount(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, SubscriptionPlan::PRO, SubscriptionStatus::ACTIVE, 999);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        self::assertTrue($this->sut->canCreateAppointment($shop));
    }

    #[Test]
    public function testCanCreateAppointmentReturnsTrueForFreeWhenCountBelow50(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, SubscriptionPlan::FREE, SubscriptionStatus::ACTIVE, 49);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        self::assertTrue($this->sut->canCreateAppointment($shop));
    }

    #[Test]
    public function testCanCreateAppointmentReturnsFalseForFreeWhenCountAt50(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, SubscriptionPlan::FREE, SubscriptionStatus::ACTIVE, 50);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        self::assertFalse($this->sut->canCreateAppointment($shop));
    }

    #[Test]
    public function testCanCreateAppointmentReturnsFalseForFreeWhenCountAbove50(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, SubscriptionPlan::FREE, SubscriptionStatus::ACTIVE, 55);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        self::assertFalse($this->sut->canCreateAppointment($shop));
    }

    #[Test]
    public function testCanCreateAppointmentReturnsFalseForCancelled(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, SubscriptionPlan::FREE, SubscriptionStatus::CANCELLED, 0);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        self::assertFalse($this->sut->canCreateAppointment($shop));
    }

    #[Test]
    public function testCanCreateAppointmentReturnsTrueWhenNoSubscriptionExistsAutoCreatesFreePlan(): void
    {
        $shop = $this->createShop();
        $this->subscriptionRepository->method('findByShop')->willReturn(null);

        self::assertTrue($this->sut->canCreateAppointment($shop));
    }

    // --- cancel ---

    #[Test]
    public function testCancelSetsStatusToCancelled(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        $result = $this->sut->cancel($shop);

        self::assertSame(SubscriptionStatus::CANCELLED, $result->getStatus());
    }

    // --- downgrade ---

    #[Test]
    public function testDowngradeSetsPlanToFreeAndEndDateNull(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, SubscriptionPlan::PRO, endDate: new \DateTimeImmutable('+30 days'));

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        $result = $this->sut->downgrade($shop);

        self::assertSame(SubscriptionPlan::FREE, $result->getPlan());
        self::assertSame(SubscriptionStatus::ACTIVE, $result->getStatus());
        self::assertNull($result->getEndDate());
    }

    #[Test]
    public function testDowngradeAlreadyFreeIsIdempotent(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, SubscriptionPlan::FREE);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        $result = $this->sut->downgrade($shop);

        self::assertSame(SubscriptionPlan::FREE, $result->getPlan());
        self::assertSame(SubscriptionStatus::ACTIVE, $result->getStatus());
    }

    // --- getByShop ---

    #[Test]
    public function testGetByShopAutoCreatesFreeSubscriptionWhenNotFound(): void
    {
        $shop = $this->createShop();
        $this->subscriptionRepository->method('findByShop')->willReturn(null);
        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $result = $this->sut->getByShop($shop);

        self::assertSame(SubscriptionPlan::FREE, $result->getPlan());
        self::assertSame(SubscriptionStatus::ACTIVE, $result->getStatus());
        self::assertSame($shop, $result->getShop());
    }

    #[Test]
    public function testGetByShopReturnsSubscription(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        self::assertSame($subscription, $this->sut->getByShop($shop));
    }

    // --- isActive ---

    #[Test]
    public function testIsActiveReturnsTrueForActiveStatus(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, status: SubscriptionStatus::ACTIVE);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        self::assertTrue($this->sut->isActive($shop));
    }

    #[Test]
    public function testIsActiveReturnsTrueForExpiredStatus(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, status: SubscriptionStatus::EXPIRED);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        self::assertTrue($this->sut->isActive($shop));
    }

    #[Test]
    public function testIsActiveReturnsFalseForCancelledStatus(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, status: SubscriptionStatus::CANCELLED);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);

        self::assertFalse($this->sut->isActive($shop));
    }

    #[Test]
    public function testIsActiveReturnsTrueWhenNoSubscriptionExistsAutoCreatesFreePlan(): void
    {
        $shop = $this->createShop();
        $this->subscriptionRepository->method('findByShop')->willReturn(null);

        self::assertTrue($this->sut->isActive($shop));
    }

    // --- incrementAppointmentCount ---

    #[Test]
    public function testIncrementAppointmentCountDelegatesToRepository(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);
        $this->subscriptionRepository->expects(self::once())
            ->method('incrementAppointmentCount')
            ->with($subscription);

        $this->sut->incrementAppointmentCount($shop);
    }

    #[Test]
    public function testIncrementAppointmentCountAutoCreatesFreeSubscriptionWhenNotFound(): void
    {
        $shop = $this->createShop();
        $this->subscriptionRepository->method('findByShop')->willReturn(null);
        $this->subscriptionRepository->expects(self::once())->method('incrementAppointmentCount');

        $this->sut->incrementAppointmentCount($shop);
    }

    // --- decrementAppointmentCount ---

    #[Test]
    public function testDecrementAppointmentCountDelegatesToRepository(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);

        $this->subscriptionRepository->method('findByShop')->willReturn($subscription);
        $this->subscriptionRepository->expects(self::once())
            ->method('decrementAppointmentCount')
            ->with($subscription);

        $this->sut->decrementAppointmentCount($shop);
    }

    #[Test]
    public function testDecrementAppointmentCountAutoCreatesFreeSubscriptionWhenNotFound(): void
    {
        $shop = $this->createShop();
        $this->subscriptionRepository->method('findByShop')->willReturn(null);
        $this->subscriptionRepository->expects(self::once())->method('decrementAppointmentCount');

        $this->sut->decrementAppointmentCount($shop);
    }

    // --- expireOverdueSubscriptions ---

    #[Test]
    public function testExpireOverdueSubscriptionsRevertsProToFree(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, SubscriptionPlan::PRO, SubscriptionStatus::ACTIVE, endDate: new \DateTimeImmutable('-1 day'));

        $this->subscriptionRepository->method('findOverdueProSubscriptions')->willReturn([$subscription]);

        $count = $this->sut->expireOverdueSubscriptions();

        self::assertSame(1, $count);
        self::assertSame(SubscriptionStatus::EXPIRED, $subscription->getStatus());
        self::assertSame(SubscriptionPlan::FREE, $subscription->getPlan());
        self::assertNull($subscription->getEndDate());
    }

    #[Test]
    public function testExpireOverdueSubscriptionsReturnsZeroWhenNoneOverdue(): void
    {
        $this->subscriptionRepository->method('findOverdueProSubscriptions')->willReturn([]);

        $count = $this->sut->expireOverdueSubscriptions();

        self::assertSame(0, $count);
    }

    // --- expireOverdueTrials ---

    #[Test]
    public function testExpireOverdueTrialsDowngradesToFreeAndActive(): void
    {
        $shop = $this->createShop();
        $pastTrialEnd = new \DateTimeImmutable('-1 day', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $subscription = $this->createSubscription($shop, SubscriptionPlan::PRO, SubscriptionStatus::ACTIVE, endDate: null);
        $subscription->setTrialEndsAt($pastTrialEnd);

        $this->subscriptionRepository->method('findOverdueTrials')->willReturn([$subscription]);
        $this->em->expects(self::once())->method('flush');

        $count = $this->sut->expireOverdueTrials();

        self::assertSame(1, $count);
        self::assertSame(SubscriptionPlan::FREE, $subscription->getPlan());
        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
        self::assertNull($subscription->getEndDate());
        self::assertSame(0, $subscription->getMonthlyAppointmentCount());
        // trialEndsAt preserved as historical marker
        self::assertSame($pastTrialEnd, $subscription->getTrialEndsAt());
    }

    #[Test]
    public function testExpireOverdueTrialsReturnsZeroWhenNoneOverdue(): void
    {
        $this->subscriptionRepository->method('findOverdueTrials')->willReturn([]);
        $this->em->expects(self::once())->method('flush');

        $count = $this->sut->expireOverdueTrials();

        self::assertSame(0, $count);
    }

    // --- resetMonthlyCounters ---

    #[Test]
    public function testResetMonthlyCountersDelegatesToRepository(): void
    {
        $this->subscriptionRepository->method('resetCountersBefore')->willReturn(5);

        $count = $this->sut->resetMonthlyCounters();

        self::assertSame(5, $count);
    }
}
