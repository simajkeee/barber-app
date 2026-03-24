<?php

declare(strict_types=1);

namespace App\Health\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class HealthController
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[Route('/health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return new JsonResponse(['status' => 'ok']);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'error',
                'detail' => $e->getMessage(),
            ], 503);
        }
    }
}
