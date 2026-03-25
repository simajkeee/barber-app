<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Dto\FacebookAuthRequest;
use App\Auth\Service\FacebookAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class FacebookAuthController
{
    public function __construct(private FacebookAuthService $facebookAuthService)
    {
    }

    #[Route('/facebook', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] FacebookAuthRequest $dto): JsonResponse
    {
        $result = $this->facebookAuthService->authenticate($dto->accessToken);
        $statusCode = $result['isNewUser'] ? 201 : 200;

        return new JsonResponse($result, $statusCode);
    }
}
