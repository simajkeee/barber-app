<?php

declare(strict_types=1);

namespace App\Subscription\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use App\Subscription\Enum\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionStatus;
use App\Subscription\Service\MomoPaymentService;
use App\Subscription\Service\SubscriptionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class CreateCheckoutSessionController
{
    public function __construct(
        private ShopManager $shopManager,
        private SubscriptionService $subscriptionService,
        private MomoPaymentService $momoPaymentService,
        private int $proMonthlyPriceVnd,
    ) {
    }

    #[Route('/subscription/checkout', methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $subscription = $this->subscriptionService->getByShop($shop);
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        if (
            SubscriptionPlan::PRO === $subscription->getPlan()
            && SubscriptionStatus::ACTIVE === $subscription->getStatus()
            && null !== $subscription->getEndDate()
            && $subscription->getEndDate() > $now
        ) {
            throw new ApiException('ALREADY_PRO', 'Shop already has an active PRO subscription.', 409);
        }

        $shopId = (string) $shop->getId();
        $orderId = \sprintf('barberpro-%s-%d', substr($shopId, 0, 8), time());

        $payUrl = $this->momoPaymentService->createPayment($orderId, $this->proMonthlyPriceVnd, $shopId);

        return new JsonResponse(['payUrl' => $payUrl]);
    }
}
