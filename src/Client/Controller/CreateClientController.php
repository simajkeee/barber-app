<?php

declare(strict_types=1);

namespace App\Client\Controller;

use App\Client\Dto\CreateClientRequest;
use App\Client\Service\ClientService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class CreateClientController
{
    public function __construct(
        private ShopManager $shopManager,
        private ClientService $clientService,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapRequestPayload] CreateClientRequest $dto,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $client = $this->clientService->create($shop, $dto);

        return new JsonResponse(ClientService::serializeClient($client), 201);
    }
}