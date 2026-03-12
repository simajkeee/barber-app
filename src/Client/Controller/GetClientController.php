<?php

declare(strict_types=1);

namespace App\Client\Controller;

use App\Client\Service\ClientService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class GetClientController
{
    public function __construct(
        private ShopManager $shopManager,
        private ClientService $clientService,
    ) {
    }

    #[Route('/{id}', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user, string $id): JsonResponse
    {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        if (!Uuid::isValid($id)) {
            throw new ApiException('CLIENT_NOT_FOUND', 'Client not found.', 404);
        }

        $client = $this->clientService->get($shop, Uuid::fromString($id));

        return new JsonResponse(ClientService::serializeClient($client));
    }
}