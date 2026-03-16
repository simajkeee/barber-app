<?php

declare(strict_types=1);

namespace App\PublicBooking\Controller;

use App\PublicBooking\Dto\BookingRequest;
use App\PublicBooking\Service\PublicBookingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class CreateBookingController
{
    public function __construct(
        private PublicBookingService $publicBookingService,
        private RateLimiterFactory $publicBookingLimiter,
    ) {
    }

    #[Route('/{slug}/book', methods: ['POST'])]
    public function __invoke(
        string $slug,
        #[MapRequestPayload] BookingRequest $dto,
        Request $request,
    ): JsonResponse {
        $limiter = $this->publicBookingLimiter->create($request->getClientIp() ?? 'unknown');
        $limiter->consume()->ensureAccepted();

        $appointment = $this->publicBookingService->book($slug, $dto);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $service = $appointment->getService();
        $startLocal = $appointment->getStartTime()->setTimezone($tz);

        return new JsonResponse([
            'appointment' => [
                'id' => (string) $appointment->getId(),
                'date' => $startLocal->format('Y-m-d'),
                'time' => $startLocal->format('H:i'),
                'service' => [
                    'id' => (string) $service->getId(),
                    'name' => $service->getName(),
                    'duration' => $service->getDurationMinutes(),
                ],
                'status' => $appointment->getStatus()->value,
            ],
            'message' => 'Đặt lịch thành công!',
        ], Response::HTTP_CREATED);
    }
}
