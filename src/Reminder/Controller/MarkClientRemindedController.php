<?php

declare(strict_types=1);

namespace App\Reminder\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Reminder\Service\ReminderService;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class MarkClientRemindedController
{
    public function __construct(
        private ShopManager $shopManager,
        private ReminderService $reminderService,
    ) {
    }

    #[Route('/{clientId}/mark-reminded', methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $user, string $clientId): JsonResponse
    {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        if (!Uuid::isValid($clientId)) {
            throw new ApiException('CLIENT_NOT_FOUND', 'Client not found.', 404);
        }

        $client = $this->reminderService->markReminded($shop, Uuid::fromString($clientId));

        return new JsonResponse([
            'clientId' => (string) $client->getId(),
            'lastRemindedAt' => $client->getLastRemindedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }
}
