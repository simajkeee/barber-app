<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Dto\LoginRequest;
use App\Auth\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class LoginController
{
    public function __construct(
        private AuthService $authService,
        private RateLimiterFactory $loginLimiter,
    ) {
    }

    #[Route('/login', methods: ['POST'])]
    public function __invoke(Request $request, #[MapRequestPayload] LoginRequest $dto): JsonResponse
    {
        $this->loginLimiter->create($request->getClientIp())->consume()->ensureAccepted();

        return new JsonResponse($this->authService->login($dto));
    }
}