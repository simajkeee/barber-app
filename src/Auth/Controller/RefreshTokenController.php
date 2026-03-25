<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Dto\RefreshTokenRequest;
use App\Auth\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class RefreshTokenController
{
    public function __construct(private AuthService $authService)
    {
    }

    #[Route('/refresh', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] RefreshTokenRequest $dto): JsonResponse
    {
        return new JsonResponse($this->authService->refresh($dto->refreshToken));
    }
}
