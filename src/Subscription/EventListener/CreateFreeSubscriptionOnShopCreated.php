<?php

declare(strict_types=1);

namespace App\Subscription\EventListener;

use App\Repository\ShopRepository;
use App\Shop\Event\ShopCreatedEvent;
use App\Subscription\Service\SubscriptionService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class CreateFreeSubscriptionOnShopCreated
{
    public function __construct(
        private ShopRepository $shopRepository,
        private SubscriptionService $subscriptionService,
    ) {
    }

    public function __invoke(ShopCreatedEvent $event): void
    {
        $shop = $this->shopRepository->find($event->shopId);
        if ($shop === null) {
            return;
        }

        $this->subscriptionService->createFreeForShop($shop);
    }
}
