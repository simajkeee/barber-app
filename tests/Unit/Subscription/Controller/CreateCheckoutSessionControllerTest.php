<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Controller;

use App\Common\Exception\ApiException;
use App\Entity\Shop;
use App\Entity\Subscription;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use App\Subscription\Controller\CreateCheckoutSessionController;
use App\Subscription\Enum\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionStatus;
use App\Subscription\Service\MomoPaymentService;
use App\Subscription\Service\SubscriptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreateCheckoutSessionController::class)]
final class CreateCheckoutSessionControllerTest extends TestCase
{
    private ShopManager&MockObject $shopManager;
    private SubscriptionService&MockObject $subscriptionService;
    private MomoPaymentService&MockObject $momoPaymentService;
    private CreateCheckoutSessionController $sut;

    protected function setUp(): void
    {
        $this->shopManager = $this->createMock(ShopManager::class);
        $this->subscriptionService = $this->createMock(SubscriptionService::class);
        $this->momoPaymentService = $this->createMock(MomoPaymentService::class);

        $this->sut = new CreateCheckoutSessionController(
            $this->shopManager,
            $this->subscriptionService,
            $this->momoPaymentService,
            299000,
        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('barber@example.com');
        $user->setFirstName('Test');
        $user->setLastName('Barber');

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

    private function createSubscription(
        Shop $shop,
        SubscriptionPlan $plan = SubscriptionPlan::FREE,
        SubscriptionStatus $status = SubscriptionStatus::ACTIVE,
        ?\DateTimeImmutable $endDate = null,
    ): Subscription {
        $subscription = new Subscription();
        $subscription->setShop($shop);
        $subscription->setPlan($plan);
        $subscription->setStatus($status);
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setEndDate($endDate);
        $subscription->setCountResetAt(new \DateTimeImmutable());

        return $subscription;
    }

    #[Test]
    public function testReturnsPayUrlOnSuccess(): void
    {
        $user = $this->createUser();
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);
        $this->momoPaymentService->expects(self::once())
            ->method('createPayment')
            ->with(
                self::matchesRegularExpression('/^barberpro-.+-\d+$/'),
                299000,
                (string) $shop->getId(),
            )
            ->willReturn('https://payment.momo.vn/pay?t=xyz');

        $response = ($this->sut)($user);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertSame('https://payment.momo.vn/pay?t=xyz', $data['payUrl']);
    }

    #[Test]
    public function testThrowsShopNotFound(): void
    {
        $user = $this->createUser();
        $this->shopManager->method('getShopForUser')->willReturn(null);

        $this->expectException(ApiException::class);

        try {
            ($this->sut)($user);
        } catch (ApiException $e) {
            self::assertSame('SHOP_NOT_FOUND', $e->errorCode);
            self::assertSame(404, $e->statusCode);

            throw $e;
        }
    }

    #[Test]
    public function testThrowsAlreadyProWhenActiveProSubscription(): void
    {
        $user = $this->createUser();
        $shop = $this->createShop();
        $subscription = $this->createSubscription(
            $shop,
            SubscriptionPlan::PRO,
            SubscriptionStatus::ACTIVE,
            new \DateTimeImmutable('+15 days'),
        );

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);
        $this->momoPaymentService->expects(self::never())->method('createPayment');

        $this->expectException(ApiException::class);

        try {
            ($this->sut)($user);
        } catch (ApiException $e) {
            self::assertSame('ALREADY_PRO', $e->errorCode);
            self::assertSame(409, $e->statusCode);

            throw $e;
        }
    }

    #[Test]
    public function testAllowsCheckoutWhenProExpired(): void
    {
        $user = $this->createUser();
        $shop = $this->createShop();
        $subscription = $this->createSubscription(
            $shop,
            SubscriptionPlan::PRO,
            SubscriptionStatus::ACTIVE,
            new \DateTimeImmutable('-1 day'),
        );

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);
        $this->momoPaymentService->method('createPayment')->willReturn('https://momo.vn/pay');

        $response = ($this->sut)($user);

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function testAllowsCheckoutWhenFreeplan(): void
    {
        $user = $this->createUser();
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop, SubscriptionPlan::FREE);

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);
        $this->momoPaymentService->method('createPayment')->willReturn('https://momo.vn/pay');

        $response = ($this->sut)($user);

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function testPropagatesMomoInitFailedException(): void
    {
        $user = $this->createUser();
        $shop = $this->createShop();
        $subscription = $this->createSubscription($shop);

        $this->shopManager->method('getShopForUser')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);
        $this->momoPaymentService->method('createPayment')
            ->willThrowException(new ApiException('MOMO_INIT_FAILED', 'MoMo error', 502));

        $this->expectException(ApiException::class);

        try {
            ($this->sut)($user);
        } catch (ApiException $e) {
            self::assertSame('MOMO_INIT_FAILED', $e->errorCode);
            self::assertSame(502, $e->statusCode);

            throw $e;
        }
    }
}
