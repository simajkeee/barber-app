<?php

declare(strict_types=1);

namespace App\Appointment\Controller;

use App\Appointment\Dto\ChangeStatusRequest;
use App\Appointment\Enum\AppointmentStatus;
use App\Appointment\Service\AppointmentService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class ChangeStatusController
{
    public function __construct(
        private ShopManager $shopManager,
        private AppointmentService $appointmentService,
        private AppointmentRepository $appointmentRepository,
    ) {
    }

    #[Route('/{id}/status', methods: ['PATCH'])]
    public function __invoke(
        #[CurrentUser] User $user,
        string $id,
        #[MapRequestPayload] ChangeStatusRequest $dto,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        if (!Uuid::isValid($id)) {
            throw new ApiException('APPOINTMENT_NOT_FOUND', 'Appointment not found.', 404);
        }

        $appointment = $this->appointmentRepository->findByShopAndId($shop, Uuid::fromString($id));
        if ($appointment === null) {
            throw new ApiException('APPOINTMENT_NOT_FOUND', 'Appointment not found.', 404);
        }

        $newStatus = AppointmentStatus::from($dto->status);
        $appointment = $this->appointmentService->changeStatus($appointment, $newStatus);

        return new JsonResponse(AppointmentService::serializeAppointment($appointment));
    }
}
