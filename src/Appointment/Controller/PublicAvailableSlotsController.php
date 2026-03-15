<?php

declare(strict_types=1);

namespace App\Appointment\Controller;

use App\Appointment\Dto\PublicAvailableSlotsQuery;
use App\Appointment\Service\AppointmentService;
use App\Common\Exception\ApiException;
use App\Repository\ShopRepository;
use App\Repository\ShopServiceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class PublicAvailableSlotsController
{
    public function __construct(
        private ShopRepository $shopRepository,
        private ShopServiceRepository $shopServiceRepository,
        private AppointmentService $appointmentService,
    ) {
    }

    #[Route('/{slug}/available-slots', methods: ['GET'])]
    public function __invoke(
        string $slug,
        #[MapQueryString] PublicAvailableSlotsQuery $query,
    ): JsonResponse {
        $shop = $this->shopRepository->findBySlug($slug);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found.', 404);
        }

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $query->date, $tz);
        if ($date === false) {
            throw new ApiException('VALIDATION_ERROR', 'Invalid date format.', 400);
        }

        $service = $this->shopServiceRepository->findOneBy([
            'id' => Uuid::fromString($query->serviceId),
            'shop' => $shop,
        ]);
        if ($service === null) {
            throw new ApiException('SERVICE_NOT_FOUND', 'Service not found.', 404);
        }

        if (!$service->isActive()) {
            throw new ApiException('SERVICE_NOT_FOUND', 'Service not found.', 404);
        }

        $date = $date->setTime(0, 0);
        $result = $this->appointmentService->getAvailableSlots($shop, $date, $service);

        return new JsonResponse($result);
    }
}
