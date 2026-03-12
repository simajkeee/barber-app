<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Dto\CreateServiceRequest;
use App\Shop\Service\ShopManager;
use App\Shop\Service\ShopServiceManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class CreateServiceController
{
    public function __construct(
        private ShopManager $shopManager,
        private ShopServiceManager $shopServiceManager,
    ) {
    }

    #[Route('/me/services', methods: ['POST'])]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapRequestPayload] CreateServiceRequest $dto,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $service = $this->shopServiceManager->createService($shop, $dto);

        return new JsonResponse(['service' => ShopServiceManager::serializeService($service)], 201);
    }
}