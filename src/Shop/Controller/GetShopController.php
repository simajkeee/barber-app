<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class GetShopController
{
    public function __construct(
        private ShopManager $shopManager,
    ) {
    }

    #[Route('/me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $schedules = $this->shopManager->getSchedule($shop);

        return new JsonResponse(['shop' => ShopManager::serializeShop($shop, $schedules)]);
    }
}