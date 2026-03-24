<?php

declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;

use App\Common\Service\CaptchaValidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(CaptchaValidatorService::class)]
final class CaptchaValidatorServiceTest extends TestCase
{
    private const SECRET_KEY = 'test-secret-key';

    #[Test]
    public function validTokenReturnsTrue(): void
    {
        $response = new MockResponse(json_encode(['success' => true]));
        $httpClient = new MockHttpClient($response);

        $sut = new CaptchaValidatorService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            self::SECRET_KEY,
        );

        self::assertTrue($sut->validate('valid-token'));
    }

    #[Test]
    public function invalidTokenReturnsFalse(): void
    {
        $response = new MockResponse(json_encode([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]));
        $httpClient = new MockHttpClient($response);

        $sut = new CaptchaValidatorService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            self::SECRET_KEY,
        );

        self::assertFalse($sut->validate('invalid-token'));
    }

    #[Test]
    public function networkErrorReturnsFalse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')
            ->willThrowException(new class ('Network error') extends \RuntimeException implements TransportExceptionInterface {});

        $sut = new CaptchaValidatorService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            self::SECRET_KEY,
        );

        self::assertFalse($sut->validate('some-token'));
    }

    #[Test]
    public function malformedResponseReturnsFalse(): void
    {
        $response = new MockResponse('not-json-at-all');
        $httpClient = new MockHttpClient($response);

        $sut = new CaptchaValidatorService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            self::SECRET_KEY,
        );

        self::assertFalse($sut->validate('some-token'));
    }

    #[Test]
    public function missingSuccessKeyReturnsFalse(): void
    {
        $response = new MockResponse(json_encode(['result' => 'ok']));
        $httpClient = new MockHttpClient($response);

        $sut = new CaptchaValidatorService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            self::SECRET_KEY,
        );

        self::assertFalse($sut->validate('some-token'));
    }

    #[Test]
    public function sendsCorrectPayload(): void
    {
        $response = new MockResponse(json_encode(['success' => true]));
        $httpClient = new MockHttpClient($response);

        $sut = new CaptchaValidatorService(
            $httpClient,
            $this->createMock(LoggerInterface::class),
            self::SECRET_KEY,
        );

        $sut->validate('my-token');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertStringContainsString('challenges.cloudflare.com/turnstile/v0/siteverify', $response->getRequestUrl());
        self::assertStringContainsString('secret=test-secret-key', $response->getRequestOptions()['body']);
        self::assertStringContainsString('response=my-token', $response->getRequestOptions()['body']);
    }
}
