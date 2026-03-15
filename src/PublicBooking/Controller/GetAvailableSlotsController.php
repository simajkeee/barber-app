<?php

declare(strict_types=1);

namespace App\PublicBooking\Controller;

use App\Common\Exception\ApiException;
use App\PublicBooking\Service\PublicBookingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class GetAvailableSlotsController
{
    public function __construct(
        private PublicBookingService $publicBookingService,
        private RateLimiterFactory $publicShopReadLimiter,
    ) {
    }

    #[Route('/{slug}/available-slots', methods: ['GET'])]
    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $limiter = $this->publicShopReadLimiter->create($request->getClientIp() ?? 'unknown');
        $limiter->consume()->ensureAccepted();

        $date = $request->query->getString('date');
        $serviceId = $request->query->getString('serviceId');

        if ('' === $date) {
            throw new ApiException('VALIDATION_ERROR', 'The "date" query parameter is required.', 400);
        }

        if ('' === $serviceId) {
            throw new ApiException('VALIDATION_ERROR', 'The "serviceId" query parameter is required.', 400);
        }

        $result = $this->publicBookingService->getAvailableSlots($slug, $date, $serviceId);

        return new JsonResponse($result);
    }
}
