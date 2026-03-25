<?php

declare(strict_types=1);

namespace App\Appointment\Controller;

use App\Appointment\Dto\UpdateAppointmentRequest;
use App\Appointment\Service\AppointmentService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class UpdateAppointmentController
{
    public function __construct(
        private ShopManager $shopManager,
        private AppointmentService $appointmentService,
        private AppointmentRepository $appointmentRepository,
    ) {
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function __invoke(
        #[CurrentUser] User $user,
        string $id,
        Request $request,
        #[MapRequestPayload] UpdateAppointmentRequest $dto,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        if (!Uuid::isValid($id)) {
            throw new ApiException('APPOINTMENT_NOT_FOUND', 'Appointment not found.', 404);
        }

        $appointment = $this->appointmentRepository->findByShopAndId($shop, Uuid::fromString($id));
        if (null === $appointment) {
            throw new ApiException('APPOINTMENT_NOT_FOUND', 'Appointment not found.', 404);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $appointment = $this->appointmentService->update($appointment, $dto, array_keys($body));

        return new JsonResponse(AppointmentService::serializeAppointment($appointment));
    }
}
