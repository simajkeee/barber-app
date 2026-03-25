<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Dto\ResetPasswordRequest;
use App\Auth\Service\PasswordResetService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class ResetPasswordController
{
    public function __construct(
        private PasswordResetService $passwordResetService,
    ) {
    }

    #[Route('/reset-password', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] ResetPasswordRequest $dto): JsonResponse
    {
        $this->passwordResetService->resetPassword($dto->token, $dto->password);

        return new JsonResponse([
            'message' => 'Password has been reset successfully.',
        ]);
    }
}
