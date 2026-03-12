<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Dto\UpdateScheduleRequest;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class UpdateScheduleController
{
    public function __construct(
        private ShopManager $shopManager,
    ) {
    }

    #[Route('/me/schedule', methods: ['PUT'])]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapRequestPayload] UpdateScheduleRequest $dto,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $schedules = $this->shopManager->updateSchedule($shop, $dto);

        return new JsonResponse(['schedule' => array_map(ShopManager::serializeScheduleEntry(...), $schedules)]);
    }
}