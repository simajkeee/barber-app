<?php

declare(strict_types=1);

namespace App\Subscription\Controller;

use App\Common\Exception\ApiException;
use App\Repository\ShopRepository;
use App\Subscription\Dto\ActivateSubscriptionRequest;
use App\Subscription\Service\SubscriptionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class AdminActivateSubscriptionController
{
    public function __construct(
        private ShopRepository $shopRepository,
        private SubscriptionService $subscriptionService,
    ) {
    }

    #[Route('/subscriptions/{shopId}/activate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(string $shopId, #[MapRequestPayload] ActivateSubscriptionRequest $dto): JsonResponse
    {
        if (!Uuid::isValid($shopId)) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found.', 404);
        }

        $shop = $this->shopRepository->find(Uuid::fromString($shopId));
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found.', 404);
        }

        $subscription = $this->subscriptionService->activate($shop, $dto->durationDays);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');

        return new JsonResponse([
            'id' => (string) $subscription->getId(),
            'shop' => [
                'id' => (string) $shop->getId(),
                'name' => $shop->getName(),
            ],
            'plan' => $subscription->getPlan()->value,
            'status' => $subscription->getStatus()->value,
            'startDate' => $subscription->getStartDate()->setTimezone($tz)->format(\DateTimeInterface::ATOM),
            'endDate' => $subscription->getEndDate()?->setTimezone($tz)->format(\DateTimeInterface::ATOM),
        ]);
    }
}
