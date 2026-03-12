<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Dto\ForgotPasswordRequest;
use App\Auth\Service\PasswordResetService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class ForgotPasswordController
{
    public function __construct(
        private PasswordResetService $passwordResetService,
        private RateLimiterFactory $passwordResetLimiter,
    ) {
    }

    #[Route('/forgot-password', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] ForgotPasswordRequest $dto): JsonResponse
    {
        $this->passwordResetLimiter->create($dto->email)->consume()->ensureAccepted();

        $this->passwordResetService->requestReset($dto->email);

        return new JsonResponse([
            'message' => 'If an account with this email exists, a password reset link has been sent.',
        ]);
    }
}