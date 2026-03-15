<?php

declare(strict_types=1);

namespace App\Appointment\Controller;

use App\Appointment\Dto\ListAppointmentsQuery;
use App\Appointment\Service\AppointmentService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class ListAppointmentsController
{
    public function __construct(
        private ShopManager $shopManager,
        private AppointmentRepository $appointmentRepository,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function __invoke(
        #[CurrentUser] User $user,
        Request $request,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        // Support both ?status=scheduled and ?status[]=scheduled query formats.
        // $request->query->all('status') throws BadRequestException for scalar values,
        // so we read from the full parameter bag and normalize manually.
        $statusValue = $request->query->all()['status'] ?? null;
        $rawStatus = match (true) {
            is_array($statusValue) => $statusValue,
            is_string($statusValue) => [$statusValue],
            default => [],
        };

        $query = new ListAppointmentsQuery(
            dateFrom: $request->query->get('dateFrom'),
            dateTo: $request->query->get('dateTo'),
            status: $rawStatus,
            clientId: $request->query->get('clientId'),
            cursor: $request->query->get('cursor'),
            limit: (int) $request->query->get('limit', 20),
        );

        $dateFrom = $query->dateFrom !== null ? $this->parseDate($query->dateFrom) : null;
        $dateTo = $query->dateTo !== null ? $this->parseDate($query->dateTo) : null;
        $clientId = $query->clientId !== null && Uuid::isValid($query->clientId)
            ? Uuid::fromString($query->clientId) : null;

        $result = $this->appointmentRepository->findByShopWithFilter(
            $shop,
            $dateFrom,
            $dateTo,
            $query->status,
            $clientId,
            $query->cursor,
            $query->limit,
        );

        return new JsonResponse([
            'data' => array_map(AppointmentService::serializeAppointment(...), $result['appointments']),
            'cursor' => $result['nextCursor'],
        ]);
    }

    private function parseDate(string $value): \DateTimeImmutable
    {
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value, $tz);
        if ($date === false) {
            throw new ApiException('VALIDATION_ERROR', "Invalid date format: {$value}", 400);
        }

        return $date->setTime(0, 0)->setTimezone(new \DateTimeZone('UTC'));
    }
}
