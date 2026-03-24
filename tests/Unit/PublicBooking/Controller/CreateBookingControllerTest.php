<?php

declare(strict_types=1);

namespace App\Tests\Unit\PublicBooking\Controller;

use App\Common\Exception\ApiException;
use App\Common\Service\CaptchaValidatorInterface;
use App\Entity\Appointment;
use App\Entity\ShopService;
use App\PublicBooking\Controller\CreateBookingController;
use App\PublicBooking\Dto\BookingRequest;
use App\PublicBooking\Service\PublicBookingService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\LimiterInterface;

#[CoversClass(CreateBookingController::class)]
final class CreateBookingControllerTest extends TestCase
{
    private PublicBookingService&MockObject $publicBookingService;
    private RateLimiterFactory&MockObject $rateLimiterFactory;
    private CaptchaValidatorInterface&MockObject $captchaValidator;
    private CreateBookingController $sut;

    protected function setUp(): void
    {
        $this->publicBookingService = $this->createMock(PublicBookingService::class);
        $this->rateLimiterFactory = $this->createMock(RateLimiterFactory::class);
        $this->captchaValidator = $this->createMock(CaptchaValidatorInterface::class);

        $limiter = $this->createMock(LimiterInterface::class);
        $rateLimit = $this->createMock(RateLimit::class);
        $limiter->method('consume')->willReturn($rateLimit);

        $this->rateLimiterFactory->method('create')->willReturn($limiter);

        $this->sut = new CreateBookingController(
            $this->publicBookingService,
            $this->rateLimiterFactory,
            $this->captchaValidator,
        );
    }

    #[Test]
    public function captchaInvalidThrowsApiException(): void
    {
        $this->captchaValidator->method('validate')->willReturn(false);

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: '01912345-6789-7abc-def0-123456789012',
            date: '2026-03-25',
            time: '10:00',
            captchaToken: 'invalid-token',
        );

        try {
            $this->sut->__invoke('test-shop', $dto, Request::create('/test-shop/book', 'POST'));
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('CAPTCHA_INVALID', $e->errorCode);
            self::assertSame(422, $e->statusCode);
        }
    }

    #[Test]
    public function captchaValidProceedsToBooking(): void
    {
        $this->captchaValidator->method('validate')->willReturn(true);

        $appointment = $this->createMock(Appointment::class);
        $service = $this->createMock(ShopService::class);
        $service->method('getId')->willReturn(\Symfony\Component\Uid\Uuid::v7());
        $service->method('getName')->willReturn('Haircut');
        $service->method('getDurationMinutes')->willReturn(30);

        $appointment->method('getId')->willReturn(\Symfony\Component\Uid\Uuid::v7());
        $appointment->method('getService')->willReturn($service);
        $appointment->method('getStartTime')->willReturn(new \DateTimeImmutable('2026-03-25 10:00:00'));
        $appointment->method('getStatus')->willReturn(\App\Appointment\Enum\AppointmentStatus::SCHEDULED);

        $this->publicBookingService->expects(self::once())
            ->method('book')
            ->willReturn($appointment);

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: '01912345-6789-7abc-def0-123456789012',
            date: '2026-03-25',
            time: '10:00',
            captchaToken: 'valid-token',
        );

        $response = $this->sut->__invoke('test-shop', $dto, Request::create('/test-shop/book', 'POST'));

        self::assertSame(201, $response->getStatusCode());
    }

    #[Test]
    public function captchaValidatedBeforeBooking(): void
    {
        $this->captchaValidator->method('validate')->willReturn(false);

        $this->publicBookingService->expects(self::never())->method('book');

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: '01912345-6789-7abc-def0-123456789012',
            date: '2026-03-25',
            time: '10:00',
            captchaToken: 'bad-token',
        );

        try {
            $this->sut->__invoke('test-shop', $dto, Request::create('/test-shop/book', 'POST'));
        } catch (ApiException) {
            // expected
        }
    }
}
