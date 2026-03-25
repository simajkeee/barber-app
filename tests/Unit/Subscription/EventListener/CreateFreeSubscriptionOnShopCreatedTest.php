<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\EventListener;

use App\Entity\Shop;
use App\Entity\User;
use App\Repository\ShopRepository;
use App\Shop\Event\ShopCreatedEvent;
use App\Subscription\EventListener\CreateFreeSubscriptionOnShopCreated;
use App\Subscription\Service\SubscriptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreateFreeSubscriptionOnShopCreated::class)]
final class CreateFreeSubscriptionOnShopCreatedTest extends TestCase
{
    private ShopRepository&MockObject $shopRepository;
    private SubscriptionService&MockObject $subscriptionService;
    private CreateFreeSubscriptionOnShopCreated $sut;

    protected function setUp(): void
    {
        $this->shopRepository = $this->createMock(ShopRepository::class);
        $this->subscriptionService = $this->createMock(SubscriptionService::class);

        $this->sut = new CreateFreeSubscriptionOnShopCreated(
            $this->shopRepository,
            $this->subscriptionService,
        );
    }

    #[Test]
    public function testCreatesFreeSubscriptionWhenShopFound(): void
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

        $event = new ShopCreatedEvent($shop->getId());

        $this->shopRepository->method('find')->with($shop->getId())->willReturn($shop);
        $this->subscriptionService->expects(self::once())
            ->method('createTrialForShop')
            ->with($shop);

        $this->sut->__invoke($event);
    }

    #[Test]
    public function testDoesNothingWhenShopNotFound(): void
    {
        $event = new ShopCreatedEvent(\Symfony\Component\Uid\Uuid::v7());

        $this->shopRepository->method('find')->willReturn(null);
        $this->subscriptionService->expects(self::never())->method('createTrialForShop');

        $this->sut->__invoke($event);
    }
}
