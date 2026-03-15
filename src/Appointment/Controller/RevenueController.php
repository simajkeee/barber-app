<?php

declare(strict_types=1);

namespace App\Appointment\Controller;

use App\Appointment\Dto\RevenueQuery;
use App\Appointment\Service\AppointmentService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class RevenueController
{
    public function __construct(
        private ShopManager $shopManager,
        private AppointmentService $appointmentService,
    ) {
    }

    #[Route('/revenue', methods: ['GET'])]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapQueryString] RevenueQuery $query,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');

        $dateFrom = \DateTimeImmutable::createFromFormat('Y-m-d', $query->dateFrom, $tz);
        if ($dateFrom === false) {
            throw new ApiException('VALIDATION_ERROR', 'Invalid dateFrom format.', 400);
        }

        $dateTo = \DateTimeImmutable::createFromFormat('Y-m-d', $query->dateTo, $tz);
        if ($dateTo === false) {
            throw new ApiException('VALIDATION_ERROR', 'Invalid dateTo format.', 400);
        }

        $result = $this->appointmentService->getRevenue($shop, $dateFrom, $dateTo);

        return new JsonResponse($result);
    }
}
