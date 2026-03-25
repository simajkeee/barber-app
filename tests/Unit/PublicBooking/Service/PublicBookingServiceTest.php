<?php

declare(strict_types=1);

namespace App\Tests\Unit\PublicBooking\Service;

use App\Appointment\Enum\AppointmentStatus;
use App\Common\Exception\ApiException;
use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\ShopService;
use App\Entity\User;
use App\Entity\WorkSchedule;
use App\Notification\Message\SendBookingConfirmationEmailMessage;
use App\Notification\Message\SendNewBookingNotificationEmailMessage;
use App\PublicBooking\Dto\BookingRequest;
use App\PublicBooking\Service\ClientResolverService;
use App\PublicBooking\Service\PublicBookingService;
use App\PublicBooking\Service\SlotAvailabilityService;
use App\Repository\AppointmentRepository;
use App\Repository\ShopRepository;
use App\Repository\ShopServiceRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Enum\DayOfWeek;
use App\Subscription\Service\AppointmentLimitChecker;
use App\Subscription\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(PublicBookingService::class)]
final class PublicBookingServiceTest extends TestCase
{
    private ShopRepository&MockObject $shopRepository;
    private ShopServiceRepository&MockObject $shopServiceRepository;
    private WorkScheduleRepository&MockObject $workScheduleRepository;
    private AppointmentRepository&MockObject $appointmentRepository;
    private SlotAvailabilityService&MockObject $slotAvailabilityService;
    private ClientResolverService&MockObject $clientResolverService;
    private EntityManagerInterface&MockObject $em;
    private ClockInterface&MockObject $clock;
    private SubscriptionService&MockObject $subscriptionService;
    private AppointmentLimitChecker&MockObject $appointmentLimitChecker;
    private MessageBusInterface&MockObject $messageBus;
    private PublicBookingService $sut;

    protected function setUp(): void
    {
        $this->shopRepository = $this->createMock(ShopRepository::class);
        $this->shopServiceRepository = $this->createMock(ShopServiceRepository::class);
        $this->workScheduleRepository = $this->createMock(WorkScheduleRepository::class);
        $this->appointmentRepository = $this->createMock(AppointmentRepository::class);
        $this->slotAvailabilityService = $this->createMock(SlotAvailabilityService::class);
        $this->clientResolverService = $this->createMock(ClientResolverService::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->subscriptionService = $this->createMock(SubscriptionService::class);
        $this->appointmentLimitChecker = $this->createMock(AppointmentLimitChecker::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        // Default: subscription is active
        $this->subscriptionService->method('isActive')->willReturn(true);

        $this->sut = $this->buildSut($this->subscriptionService);
    }

    private function buildSut(?SubscriptionService $subscriptionService = null, ?MessageBusInterface $messageBus = null): PublicBookingService
    {
        return new PublicBookingService(
            $this->shopRepository,
            $this->shopServiceRepository,
            $this->workScheduleRepository,
            $this->appointmentRepository,
            $this->slotAvailabilityService,
            $this->clientResolverService,
            $this->em,
            $this->clock,
            $subscriptionService ?? $this->subscriptionService,
            $this->appointmentLimitChecker,
            $messageBus ?? $this->messageBus,
        );
    }

    // --- getShopInfo ---

    #[Test]
    public function getShopInfoReturnsCorrectData(): void
    {
        $shop = $this->createShop();

        $this->shopRepository->method('findBySlug')
            ->with('test-shop')
            ->willReturn($shop);

        $schedule = new WorkSchedule();
        $schedule->setShop($shop);
        $schedule->setDayOfWeek(DayOfWeek::MONDAY);
        $schedule->setOpenTime(new \DateTimeImmutable('09:00'));
        $schedule->setCloseTime(new \DateTimeImmutable('18:00'));
        $schedule->setIsOpen(true);

        $this->workScheduleRepository->method('findByShop')
            ->willReturn([$schedule]);

        $service = $this->createService($shop);

        $this->shopServiceRepository->method('findByShop')
            ->with($shop)
            ->willReturn([$service]);

        $result = $this->sut->getShopInfo('test-shop');

        self::assertSame('Test Shop', $result['name']);
        self::assertSame('123 Test St', $result['address']);
        self::assertSame('0901234567', $result['phone']);
        self::assertNotNull($result['workingHours']['monday']);
        self::assertSame('09:00', $result['workingHours']['monday']['open']);
        self::assertSame('18:00', $result['workingHours']['monday']['close']);
        self::assertNull($result['workingHours']['tuesday']);
        self::assertCount(1, $result['services']);
        self::assertSame('Haircut', $result['services'][0]['name']);
    }

    #[Test]
    public function getShopInfoThrowsWhenShopNotFound(): void
    {
        $this->shopRepository->method('findBySlug')->willReturn(null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Shop not found.');

        $this->sut->getShopInfo('non-existent');
    }

    // --- getAvailableSlots ---

    #[Test]
    public function getAvailableSlotsReturnsCorrectData(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);

        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $this->shopServiceRepository->method('findOneBy')
            ->willReturn($service);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-15 06:00:00', $tz));

        $this->slotAvailabilityService->method('getSlots')
            ->willReturn([
                ['time' => '09:00', 'available' => true],
                ['time' => '09:30', 'available' => false],
            ]);

        $result = $this->sut->getAvailableSlots('test-shop', '2026-03-16', (string) $service->getId());

        self::assertSame('2026-03-16', $result['date']);
        self::assertCount(2, $result['slots']);
        self::assertTrue($result['slots'][0]['available']);
        self::assertFalse($result['slots'][1]['available']);
    }

    #[Test]
    public function getAvailableSlotsThrowsForInvalidDateFormat(): void
    {
        $shop = $this->createShop();
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-15 06:00:00', $tz));

        $this->expectException(ApiException::class);

        $this->sut->getAvailableSlots('test-shop', 'invalid-date', 'some-uuid');
    }

    #[Test]
    public function getAvailableSlotsThrowsForPastDate(): void
    {
        $shop = $this->createShop();
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-15 10:00:00', $tz));

        try {
            $this->sut->getAvailableSlots('test-shop', '2026-03-14', 'some-uuid');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('DATE_IN_PAST', $e->errorCode);
        }
    }

    #[Test]
    public function getAvailableSlotsThrowsForDateTooFarAhead(): void
    {
        $shop = $this->createShop();
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-15 10:00:00', $tz));

        try {
            $this->sut->getAvailableSlots('test-shop', '2026-05-15', 'some-uuid');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('DATE_TOO_FAR_AHEAD', $e->errorCode);
        }
    }

    #[Test]
    public function getAvailableSlotsThrowsForInactiveService(): void
    {
        $shop = $this->createShop();
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $service = $this->createService($shop);
        $service->setIsActive(false);

        $this->shopServiceRepository->method('findOneBy')
            ->willReturn($service);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-15 06:00:00', $tz));

        try {
            $this->sut->getAvailableSlots('test-shop', '2026-03-16', (string) $service->getId());
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('SERVICE_NOT_FOUND', $e->errorCode);
        }
    }

    // --- book ---

    #[Test]
    public function bookCreatesAppointmentSuccessfully(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);
        $client = $this->createClient($shop);

        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 07:00:00', $tz));

        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '18:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([]);
        $this->appointmentRepository->method('countByShopPhoneAndDateRange')->willReturn(0);

        $this->clientResolverService->method('resolveClient')->willReturn($client);

        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(Appointment::class));
        $this->em->expects(self::once())->method('flush');

        $dto = new BookingRequest(
            clientName: 'Nguyen Van A',
            clientPhone: '0901234567',
            serviceId: (string) $service->getId(),
            date: '2026-03-16',
            time: '10:00',
        );

        $appointment = $this->sut->book('test-shop', $dto);

        self::assertSame($shop, $appointment->getShop());
        self::assertSame($client, $appointment->getClient());
        self::assertSame($service, $appointment->getService());
        self::assertSame(AppointmentStatus::SCHEDULED, $appointment->getStatus());
    }

    #[Test]
    public function bookThrowsWhenSlotIsNotOn30MinBoundary(): void
    {
        $shop = $this->createShop();
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 07:00:00', $tz));

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: 'some-uuid',
            date: '2026-03-16',
            time: '10:15',
        );

        try {
            $this->sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('VALIDATION_ERROR', $e->errorCode);
            self::assertStringContainsString('30-minute boundary', $e->getMessage());
        }
    }

    #[Test]
    public function bookThrowsWhenTooShortNotice(): void
    {
        $shop = $this->createShop();
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        // Current time is 09:30, trying to book 10:00 (only 30 min ahead)
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 09:30:00', $tz));

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: 'some-uuid',
            date: '2026-03-16',
            time: '10:00',
        );

        try {
            $this->sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('TOO_SHORT_NOTICE', $e->errorCode);
        }
    }

    #[Test]
    public function bookThrowsWhenOutsideWorkingHours(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);

        $this->shopRepository->method('findBySlug')->willReturn($shop);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 05:00:00', $tz));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '18:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: (string) $service->getId(),
            date: '2026-03-16',
            time: '07:00',
        );

        try {
            $this->sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('OUTSIDE_WORKING_HOURS', $e->errorCode);
        }
    }

    #[Test]
    public function bookThrowsWhenSlotAlreadyBooked(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);

        $this->shopRepository->method('findBySlug')->willReturn($shop);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 07:00:00', $tz));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '18:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $existingAppt = new Appointment();
        $this->appointmentRepository->method('findNonCancelledInRange')
            ->willReturn([$existingAppt]);

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: (string) $service->getId(),
            date: '2026-03-16',
            time: '10:00',
        );

        try {
            $this->sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('SLOT_UNAVAILABLE', $e->errorCode);
            self::assertSame(409, $e->statusCode);
        }
    }

    #[Test]
    public function bookThrowsWhenPhoneRateLimitExceeded(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);

        $this->shopRepository->method('findBySlug')->willReturn($shop);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 07:00:00', $tz));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '18:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([]);
        $this->appointmentRepository->method('countByShopPhoneAndDateRange')->willReturn(5);

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: (string) $service->getId(),
            date: '2026-03-16',
            time: '10:00',
        );

        try {
            $this->sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('BOOKING_RATE_LIMIT_EXCEEDED', $e->errorCode);
            self::assertSame(429, $e->statusCode);
        }
    }

    #[Test]
    public function bookThrowsWhenDateInPast(): void
    {
        $shop = $this->createShop();
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 10:00:00', $tz));

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: 'some-uuid',
            date: '2026-03-15',
            time: '10:00',
        );

        try {
            $this->sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('DATE_IN_PAST', $e->errorCode);
        }
    }

    #[Test]
    public function bookThrowsWhenShopClosedOnDay(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);

        $this->shopRepository->method('findBySlug')->willReturn($shop);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 05:00:00', $tz));

        $this->workScheduleRepository->method('findByShopAndDay')->willReturn(null);

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: (string) $service->getId(),
            date: '2026-03-16',
            time: '10:00',
        );

        try {
            $this->sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('OUTSIDE_WORKING_HOURS', $e->errorCode);
        }
    }

    #[Test]
    public function bookThrowsWhenServiceNotFound(): void
    {
        $shop = $this->createShop();
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 05:00:00', $tz));

        $this->shopServiceRepository->method('findOneBy')->willReturn(null);

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: '01912345-6789-7abc-def0-123456789012',
            date: '2026-03-16',
            time: '10:00',
        );

        try {
            $this->sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('SERVICE_NOT_FOUND', $e->errorCode);
        }
    }

    #[Test]
    public function getShopInfoThrowsWhenSubscriptionInactive(): void
    {
        $shop = $this->createShop();
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $inactiveSubscription = $this->createMock(SubscriptionService::class);
        $inactiveSubscription->method('isActive')->willReturn(false);
        $sut = $this->buildSut($inactiveSubscription);

        try {
            $sut->getShopInfo('test-shop');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('SHOP_UNAVAILABLE', $e->errorCode);
            self::assertSame(403, $e->statusCode);
        }
    }

    #[Test]
    public function bookThrowsWhenAppointmentLimitReached(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);

        $this->shopRepository->method('findBySlug')->willReturn($shop);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 07:00:00', $tz));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '18:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([]);

        $this->appointmentLimitChecker->method('check')
            ->willThrowException(new ApiException('APPOINTMENT_LIMIT_REACHED', 'Monthly appointment limit reached.', 403));

        $dto = new BookingRequest(
            clientName: 'Test',
            clientPhone: '0901234567',
            serviceId: (string) $service->getId(),
            date: '2026-03-16',
            time: '10:00',
        );

        try {
            $this->sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('APPOINTMENT_LIMIT_REACHED', $e->errorCode);
            self::assertSame(403, $e->statusCode);
        }
    }

    #[Test]
    public function bookDispatchesConfirmationEmailWhenClientHasEmail(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);
        $client = $this->createClient($shop);
        $client->setEmail('client@example.com');

        $this->setupForBook($shop, $service, $client);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $dispatched = [];
        $messageBus->method('dispatch')
            ->willReturnCallback(function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        $sut = $this->buildSut(messageBus: $messageBus);
        $dto = $this->createBookingDto($service);
        $sut->book('test-shop', $dto);

        $confirmations = array_filter($dispatched, fn ($m) => $m instanceof SendBookingConfirmationEmailMessage);
        self::assertCount(1, $confirmations);
        $msg = array_values($confirmations)[0];
        self::assertSame('client@example.com', $msg->clientEmail);
        self::assertSame('Nguyen', $msg->clientFirstName);
        self::assertSame('Haircut', $msg->serviceName);
        self::assertSame(30, $msg->durationMinutes);
        self::assertSame('Test Shop', $msg->shopName);
        self::assertSame('123 Test St', $msg->shopAddress);
        self::assertSame('0901234567', $msg->shopPhone);
        self::assertSame('vi', $msg->locale);
    }

    #[Test]
    public function bookDoesNotDispatchConfirmationWhenClientHasNoEmail(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);
        $client = $this->createClient($shop);
        // no email set on client

        $this->setupForBook($shop, $service, $client);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $dispatched = [];
        $messageBus->method('dispatch')
            ->willReturnCallback(function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        $sut = $this->buildSut(messageBus: $messageBus);
        $dto = $this->createBookingDto($service);
        $sut->book('test-shop', $dto);

        $confirmations = array_filter($dispatched, fn ($m) => $m instanceof SendBookingConfirmationEmailMessage);
        self::assertCount(0, $confirmations);
    }

    #[Test]
    public function bookAlwaysDispatchesOwnerNotification(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);
        $client = $this->createClient($shop);
        // no client email — owner notification should still be sent

        $this->setupForBook($shop, $service, $client);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $dispatched = [];
        $messageBus->method('dispatch')
            ->willReturnCallback(function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        $sut = $this->buildSut(messageBus: $messageBus);
        $dto = $this->createBookingDto($service);
        $sut->book('test-shop', $dto);

        $ownerNotifs = array_filter($dispatched, fn ($m) => $m instanceof SendNewBookingNotificationEmailMessage);
        self::assertCount(1, $ownerNotifs);
        $msg = array_values($ownerNotifs)[0];
        self::assertSame('test@example.com', $msg->ownerEmail);
        self::assertSame('Nguyen Van A', $msg->clientFullName);
        self::assertSame('0901234567', $msg->clientPhone);
        self::assertSame('Haircut', $msg->serviceName);
        self::assertSame(30, $msg->durationMinutes);
        self::assertSame(100000, $msg->price);
        self::assertSame('vi', $msg->locale);
    }

    #[Test]
    public function bookOwnerNotificationUsesOwnerLocale(): void
    {
        $shop = $this->createShop();
        $shop->getOwner()->setLocale(\App\Auth\Enum\UserLocale::EN);
        $service = $this->createService($shop);
        $client = $this->createClient($shop);

        $this->setupForBook($shop, $service, $client);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $dispatched = [];
        $messageBus->method('dispatch')
            ->willReturnCallback(function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        $sut = $this->buildSut(messageBus: $messageBus);
        $dto = $this->createBookingDto($service);
        $sut->book('test-shop', $dto);

        $ownerNotifs = array_filter($dispatched, fn ($m) => $m instanceof SendNewBookingNotificationEmailMessage);
        $msg = array_values($ownerNotifs)[0];
        self::assertSame('en', $msg->locale);
    }

    #[Test]
    public function bookDoesNotDispatchWhenFlushFails(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);
        $client = $this->createClient($shop);
        $client->setEmail('client@example.com');

        $this->setupForBook($shop, $service, $client);

        $driverException = $this->createMock(\Doctrine\DBAL\Exception\DriverException::class);
        $driverException->method('getSQLState')->willReturn('23P01');
        $this->em->method('flush')->willThrowException($driverException);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatch');

        $sut = $this->buildSut(messageBus: $messageBus);
        $dto = $this->createBookingDto($service);

        try {
            $sut->book('test-shop', $dto);
            self::fail('Expected ApiException');
        } catch (ApiException) {
            // expected
        }
    }

    #[Test]
    public function bookIncrementsAppointmentCountOnSuccess(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);
        $client = $this->createClient($shop);

        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 07:00:00', $tz));

        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '18:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([]);
        $this->appointmentRepository->method('countByShopPhoneAndDateRange')->willReturn(0);

        $this->clientResolverService->method('resolveClient')->willReturn($client);

        $this->subscriptionService->expects(self::once())
            ->method('incrementAppointmentCount')
            ->with($shop);

        $dto = new BookingRequest(
            clientName: 'Nguyen Van A',
            clientPhone: '0901234567',
            serviceId: (string) $service->getId(),
            date: '2026-03-16',
            time: '10:00',
        );

        $this->sut->book('test-shop', $dto);
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Test St');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    private function createService(Shop $shop): ShopService
    {
        $service = new ShopService();
        $service->setShop($shop);
        $service->setName('Haircut');
        $service->setDurationMinutes(30);
        $service->setPrice(100000);

        return $service;
    }

    private function createClient(Shop $shop): Client
    {
        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName('Nguyen');
        $client->setLastName('Van A');
        $client->setPhone('0901234567');

        return $client;
    }

    private function setupForBook(Shop $shop, ShopService $service, Client $client): void
    {
        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 07:00:00', $tz));

        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $schedule = new WorkSchedule();
        $schedule->setShop($shop);
        $schedule->setDayOfWeek(DayOfWeek::MONDAY);
        $schedule->setOpenTime(new \DateTimeImmutable('09:00'));
        $schedule->setCloseTime(new \DateTimeImmutable('18:00'));
        $schedule->setIsOpen(true);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([]);
        $this->appointmentRepository->method('countByShopPhoneAndDateRange')->willReturn(0);

        $this->clientResolverService->method('resolveClient')->willReturn($client);
    }

    private function createBookingDto(ShopService $service): BookingRequest
    {
        return new BookingRequest(
            clientName: 'Nguyen Van A',
            clientPhone: '0901234567',
            serviceId: (string) $service->getId(),
            date: '2026-03-16',
            time: '10:00',
        );
    }

    private function createSchedule(Shop $shop, DayOfWeek $day, string $open, string $close): WorkSchedule
    {
        $schedule = new WorkSchedule();
        $schedule->setShop($shop);
        $schedule->setDayOfWeek($day);
        $schedule->setOpenTime(new \DateTimeImmutable($open));
        $schedule->setCloseTime(new \DateTimeImmutable($close));
        $schedule->setIsOpen(true);

        return $schedule;
    }
}
