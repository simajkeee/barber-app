<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Entity\User;
use App\Shop\Dto\CreateShopRequest;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class CreateShopController
{
    public function __construct(
        private ShopManager $shopManager,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $user, #[MapRequestPayload] CreateShopRequest $dto): JsonResponse
    {
        $shop = $this->shopManager->createShop($user, $dto);
        $schedules = $this->shopManager->getSchedule($shop);

        return new JsonResponse(['shop' => ShopManager::serializeShop($shop, $schedules)], 201);
    }
}
