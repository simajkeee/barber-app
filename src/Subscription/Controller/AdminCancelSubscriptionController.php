<?php

declare(strict_types=1);

namespace App\Subscription\Controller;

use App\Common\Exception\ApiException;
use App\Repository\ShopRepository;
use App\Subscription\Service\SubscriptionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class AdminCancelSubscriptionController
{
    public function __construct(
        private ShopRepository $shopRepository,
        private SubscriptionService $subscriptionService,
    ) {
    }

    #[Route('/subscriptions/{shopId}/cancel', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(string $shopId): JsonResponse
    {
        if (!Uuid::isValid($shopId)) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found.', 404);
        }

        $shop = $this->shopRepository->find(Uuid::fromString($shopId));
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found.', 404);
        }

        $subscription = $this->subscriptionService->cancel($shop);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');

        return new JsonResponse([
            'id' => (string) $subscription->getId(),
            'plan' => $subscription->getPlan()->value,
            'status' => $subscription->getStatus()->value,
            'endDate' => $subscription->getEndDate()?->setTimezone($tz)->format(\DateTimeInterface::ATOM),
        ]);
    }
}
