<?php

declare(strict_types=1);

namespace App\Tests\Unit\Health\Controller;

use App\Health\Controller\HealthController;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HealthControllerTest extends TestCase
{
    private Connection&MockObject $connection;
    private HealthController $controller;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->controller = new HealthController($this->connection);
    }

    public function testHealthyReturns200WithStatusOk(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT 1');

        $response = ($this->controller)();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"status":"ok"}', (string) $response->getContent());
    }

    public function testDatabaseDownReturns503WithErrorDetail(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new \Exception('connection refused'));

        $response = ($this->controller)();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(503, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"status":"error","detail":"connection refused"}',
            (string) $response->getContent(),
        );
    }

    public function testDriverErrorReturns503WithDetailKey(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new \Error('driver fatal error'));

        $response = ($this->controller)();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(503, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('detail', $data);
        $this->assertSame('error', $data['status']);
    }
}
