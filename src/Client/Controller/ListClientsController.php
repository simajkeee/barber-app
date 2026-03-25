<?php

declare(strict_types=1);

namespace App\Client\Controller;

use App\Client\Dto\ClientListFilter;
use App\Client\Service\ClientService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class ListClientsController
{
    public function __construct(
        private ShopManager $shopManager,
        private ClientService $clientService,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapQueryString] ?ClientListFilter $filter = null,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $result = $this->clientService->list($shop, $filter ?? new ClientListFilter());

        return new JsonResponse($result);
    }
}
