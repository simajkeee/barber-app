<?php

declare(strict_types=1);

namespace App\Client\Controller;

use App\Client\Dto\UpdateClientRequest;
use App\Client\Service\ClientService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class UpdateClientController
{
    public function __construct(
        private ShopManager $shopManager,
        private ClientService $clientService,
    ) {
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function __invoke(
        #[CurrentUser] User $user,
        string $id,
        Request $request,
        #[MapRequestPayload] UpdateClientRequest $dto,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        if (!Uuid::isValid($id)) {
            throw new ApiException('CLIENT_NOT_FOUND', 'Client not found.', 404);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $client = $this->clientService->update($shop, Uuid::fromString($id), $dto, array_keys($body));

        return new JsonResponse(ClientService::serializeClient($client));
    }
}