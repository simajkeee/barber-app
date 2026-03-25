<?php

declare(strict_types=1);

namespace App\Tests\Unit\Common\EventListener;

use App\Common\EventListener\ApiExceptionListener;
use App\Common\Exception\ApiException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[CoversClass(ApiExceptionListener::class)]
final class ApiExceptionListenerTest extends TestCase
{
    private ApiExceptionListener $sut;

    protected function setUp(): void
    {
        $this->sut = new ApiExceptionListener();
    }

    private function createEvent(string $path, \Throwable $exception): ExceptionEvent
    {
        $request = Request::create($path);
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    // --- Non-API paths are ignored ---

    #[Test]
    public function testNonApiPathIsIgnored(): void
    {
        $event = $this->createEvent('/admin/dashboard', new ApiException('CODE', 'msg', 400));

        ($this->sut)($event);

        self::assertNull($event->getResponse());
    }

    // --- ApiException handling ---

    #[Test]
    public function testApiExceptionReturnsJsonWithCodeAndMessage(): void
    {
        $event = $this->createEvent('/api/v1/auth/register', new ApiException('EMAIL_ALREADY_EXISTS', 'Duplicate.', 409));

        ($this->sut)($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(409, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertSame('EMAIL_ALREADY_EXISTS', $data['code']);
        self::assertSame('Duplicate.', $data['message']);
        self::assertArrayNotHasKey('details', $data);
    }

    #[Test]
    public function testApiExceptionWithDetailsIncludesDetails(): void
    {
        $details = [['field' => 'email', 'message' => 'invalid']];
        $event = $this->createEvent('/api/v1/test', new ApiException('ERR', 'msg', 400, $details));

        ($this->sut)($event);

        $data = json_decode($event->getResponse()->getContent(), true);
        self::assertSame($details, $data['details']);
    }

    // --- ValidationFailedException handling ---

    #[Test]
    public function testValidationFailedExceptionReturns400WithFieldErrors(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Must not be blank.', null, [], null, 'email', ''),
            new ConstraintViolation('Too short.', null, [], null, 'password', 'ab'),
        ]);
        $validationException = new ValidationFailedException('value', $violations);
        $httpException = new HttpException(422, 'Validation failed', $validationException);

        $event = $this->createEvent('/api/v1/auth/register', $httpException);

        ($this->sut)($event);

        $response = $event->getResponse();
        self::assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertSame('VALIDATION_ERROR', $data['code']);
        self::assertCount(2, $data['details']);
        self::assertSame('email', $data['details'][0]['field']);
        self::assertSame('Must not be blank.', $data['details'][0]['message']);
        self::assertSame('password', $data['details'][1]['field']);
    }

    // --- RateLimitExceededException handling ---

    #[Test]
    public function testRateLimitExceededReturns429WithRetryAfter(): void
    {
        $retryAfter = new \DateTimeImmutable('+60 seconds');
        $rateLimit = $this->createStub(RateLimit::class);
        $rateLimit->method('getRetryAfter')->willReturn($retryAfter);
        $rateLimit->method('getLimit')->willReturn(5);

        $exception = new RateLimitExceededException($rateLimit);
        $event = $this->createEvent('/api/v1/auth/register', $exception);

        ($this->sut)($event);

        $response = $event->getResponse();
        self::assertSame(429, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertSame(429, $data['code']);
        self::assertSame('Too many requests.', $data['message']);
        self::assertNotEmpty($response->headers->get('Retry-After'));
    }

    // --- Generic HttpException handling ---

    #[Test]
    public function testGenericHttpExceptionReturnsMatchingStatusCode(): void
    {
        $event = $this->createEvent('/api/v1/something', new HttpException(403, 'Forbidden'));

        ($this->sut)($event);

        $response = $event->getResponse();
        self::assertSame(403, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertSame(403, $data['code']);
        self::assertSame('Forbidden', $data['message']);
    }

    #[Test]
    public function testGenericHttpException404(): void
    {
        $event = $this->createEvent('/api/v1/missing', new HttpException(404, 'Not Found'));

        ($this->sut)($event);

        self::assertSame(404, $event->getResponse()->getStatusCode());
    }

    // --- Unhandled exceptions pass through ---

    #[Test]
    public function testUnhandledExceptionDoesNotSetResponse(): void
    {
        $event = $this->createEvent('/api/v1/something', new \RuntimeException('Unexpected'));

        ($this->sut)($event);

        self::assertNull($event->getResponse());
    }

    // --- Path matching ---

    #[Test]
    #[DataProvider('apiPathProvider')]
    public function testApiPathDetection(string $path, bool $shouldHandle): void
    {
        $event = $this->createEvent($path, new ApiException('CODE', 'msg', 400));

        ($this->sut)($event);

        if ($shouldHandle) {
            self::assertNotNull($event->getResponse());
        } else {
            self::assertNull($event->getResponse());
        }
    }

    public static function apiPathProvider(): iterable
    {
        yield 'api v1 path' => ['/api/v1/auth/login', true];
        yield 'api root' => ['/api/test', true];
        yield 'non-api path' => ['/admin', false];
        yield 'root path' => ['/', false];
        yield 'partial match' => ['/apiary', false];
    }
}
