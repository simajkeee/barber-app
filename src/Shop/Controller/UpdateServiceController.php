<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Repository\ShopServiceRepository;
use App\Shop\Dto\UpdateServiceRequest;
use App\Shop\Service\ShopManager;
use App\Shop\Service\ShopServiceManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class UpdateServiceController
{
    public function __construct(
        private ShopManager $shopManager,
        private ShopServiceRepository $shopServiceRepository,
        private ShopServiceManager $shopServiceManager,
    ) {
    }

    #[Route('/me/services/{id}', methods: ['PUT'])]
    public function __invoke(
        Uuid $id,
        #[CurrentUser] User $user,
        #[MapRequestPayload] UpdateServiceRequest $dto,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $service = $this->shopServiceRepository->find($id);
        if (null === $service || $service->getShop()->getId()->toRfc4122() !== $shop->getId()->toRfc4122()) {
            throw new ApiException('SERVICE_NOT_FOUND', 'Service not found.', 404);
        }

        $service = $this->shopServiceManager->updateService($service, $dto);

        return new JsonResponse(['service' => ShopServiceManager::serializeService($service)]);
    }
}
