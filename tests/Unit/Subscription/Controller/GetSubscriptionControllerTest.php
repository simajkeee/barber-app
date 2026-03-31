<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Controller;

use App\Common\Exception\ApiException;
use App\Entity\Shop;
use App\Entity\Subscription;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use App\Subscription\Controller\GetSubscriptionController;
use App\Subscription\Enum\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionStatus;
use App\Subscription\Service\SubscriptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetSubscriptionController::class)]
final class GetSubscriptionControllerTest extends TestCase
{
    private ShopManager&MockObject $shopManager;
    private SubscriptionService&MockObject $subscriptionService;
    private GetSubscriptionController $sut;

    protected function setUp(): void
    {
        $this->shopManager = $this->createMock(ShopManager::class);
        $this->subscriptionService = $this->createMock(SubscriptionService::class);

        $this->sut = new GetSubscriptionController(
            $this->shopManager,
            $this->subscriptionService,
        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setFirstName('Test');
        $user->setLastName('Owner');

        return $user;
    }

    private function createShop(): Shop
    {
        $shop = new Shop();
        $shop->setOwner($this->createUser());
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    private function createSubscription(Shop $shop): Subscription
    {
        $subscription = new Subscription();
        $subscription->setShop($shop);
        $subscription->setPlan(SubscriptionPlan::PRO);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setCountResetAt(new \DateTimeImmutable());

        return $subscription;
    }

    #[Test]
    public function testThrowsShopNotFoundWhenNoShop(): void
    {
        $this->shopManager->method('getShopForUser')->willReturn(null);

        $this->expectException(ApiException::class);

        try {
            ($this->sut)($this->createUser());
        } catch (ApiException $e) {
            self::assertSame('SHOP_NOT_FOUND', $e->errorCode);
            self::assertSame(404, $e->statusCode);

            throw $e;
        }
    }

    #[Test]
    public function testDaysRemainingUsesCeilNotFloor(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);
        $subscription->setEndDate(new \DateTimeImmutable('+23 hours'));

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $response = ($this->sut)($this->createUser());
        $data = json_decode($response->getContent(), true);

        // ceil(23h / 24h) = 1, floor would give 0
        self::assertSame(1, $data['daysRemaining']);
    }

    #[Test]
    public function testDaysRemainingIsZeroWhenExpired(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);
        $subscription->setEndDate(new \DateTimeImmutable('-1 second'));

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $response = ($this->sut)($this->createUser());
        $data = json_decode($response->getContent(), true);

        self::assertSame(0, $data['daysRemaining']);
    }

    #[Test]
    public function testDaysRemainingIsNullWhenNoEndDate(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);
        $subscription->setPlan(SubscriptionPlan::FREE);
        $subscription->setEndDate(null);

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $response = ($this->sut)($this->createUser());
        $data = json_decode($response->getContent(), true);

        self::assertNull($data['daysRemaining']);
    }

    #[Test]
    public function testIsExpiringSoonWhenSevenDaysOrLess(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);
        $subscription->setEndDate(new \DateTimeImmutable('+3 days'));

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $response = ($this->sut)($this->createUser());
        $data = json_decode($response->getContent(), true);

        self::assertTrue($data['isExpiringSoon']);
    }

    #[Test]
    public function testIsNotExpiringSoonWhenMoreThanSevenDays(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);
        $subscription->setEndDate(new \DateTimeImmutable('+30 days'));

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $response = ($this->sut)($this->createUser());
        $data = json_decode($response->getContent(), true);

        self::assertFalse($data['isExpiringSoon']);
    }

    #[Test]
    public function testIsExpiringSoonFalseWhenNoEndDate(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);
        $subscription->setPlan(SubscriptionPlan::FREE);
        $subscription->setEndDate(null);

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $response = ($this->sut)($this->createUser());
        $data = json_decode($response->getContent(), true);

        self::assertFalse($data['isExpiringSoon']);
    }

    #[Test]
    public function testResponseIncludesRequiredFields(): void
    {
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);
        $subscription->setEndDate(new \DateTimeImmutable('+30 days'));

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $response = ($this->sut)($this->createUser());

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('plan', $data);
        self::assertArrayHasKey('status', $data);
        self::assertArrayHasKey('startDate', $data);
        self::assertArrayHasKey('endDate', $data);
        self::assertArrayHasKey('daysRemaining', $data);
        self::assertArrayHasKey('isExpiringSoon', $data);
        self::assertArrayHasKey('usage', $data);
        self::assertArrayHasKey('trial', $data);
    }
}