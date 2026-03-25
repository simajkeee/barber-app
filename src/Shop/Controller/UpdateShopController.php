<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Dto\UpdateShopRequest;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class UpdateShopController
{
    public function __construct(
        private ShopManager $shopManager,
    ) {
    }

    #[Route('/me', methods: ['PUT'])]
    public function __invoke(
        Request $request,
        #[CurrentUser] User $user,
        #[MapRequestPayload] UpdateShopRequest $dto,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $explicitFields = array_keys($body);

        $shop = $this->shopManager->updateShop($shop, $dto, $explicitFields);
        $schedules = $this->shopManager->getSchedule($shop);

        return new JsonResponse(['shop' => ShopManager::serializeShop($shop, $schedules)]);
    }
}
