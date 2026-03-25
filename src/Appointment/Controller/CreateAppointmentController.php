<?php

declare(strict_types=1);

namespace App\Appointment\Controller;

use App\Appointment\Dto\CreateAppointmentRequest;
use App\Appointment\Service\AppointmentService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class CreateAppointmentController
{
    public function __construct(
        private ShopManager $shopManager,
        private AppointmentService $appointmentService,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapRequestPayload] CreateAppointmentRequest $dto,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $appointment = $this->appointmentService->create($dto, $shop);

        return new JsonResponse(AppointmentService::serializeAppointment($appointment), 201);
    }
}
