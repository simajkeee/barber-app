<?php

declare(strict_types=1);

namespace App\PublicBooking\Controller;

use App\PublicBooking\Service\PublicBookingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class GetPublicShopController
{
    public function __construct(
        private PublicBookingService $publicBookingService,
        private RateLimiterFactory $publicShopReadLimiter,
    ) {
    }

    #[Route('/{slug}', methods: ['GET'])]
    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $limiter = $this->publicShopReadLimiter->create($request->getClientIp() ?? 'unknown');
        $limiter->consume()->ensureAccepted();

        $shopInfo = $this->publicBookingService->getShopInfo($slug);

        return new JsonResponse($shopInfo);
    }
}
