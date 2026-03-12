<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use App\Shop\Service\ShopServiceManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class ListServicesController
{
    public function __construct(
        private ShopManager $shopManager,
        private ShopServiceManager $shopServiceManager,
    ) {
    }

    #[Route('/me/services', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $includeInactive = filter_var($request->query->get('includeInactive', 'false'), FILTER_VALIDATE_BOOLEAN);
        $services = $this->shopServiceManager->listServices($shop, $includeInactive);

        return new JsonResponse(['services' => array_map(ShopServiceManager::serializeService(...), $services)]);
    }
}