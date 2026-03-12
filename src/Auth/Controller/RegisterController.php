<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Dto\RegisterRequest;
use App\Auth\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class RegisterController
{
    public function __construct(
        private AuthService $authService,
        private RateLimiterFactory $registrationLimiter,
    ) {
    }

    #[Route('/register', methods: ['POST'])]
    public function __invoke(Request $request, #[MapRequestPayload] RegisterRequest $dto): JsonResponse
    {
        $this->registrationLimiter->create($request->getClientIp())->consume()->ensureAccepted();

        return new JsonResponse($this->authService->register($dto), 201);
    }
}