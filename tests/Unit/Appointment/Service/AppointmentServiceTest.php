<?php

declare(strict_types=1);

namespace App\Tests\Unit\Appointment\Service;

use App\Appointment\Dto\CreateAppointmentRequest;
use App\Appointment\Dto\UpdateAppointmentRequest;
use App\Appointment\Enum\AppointmentStatus;
use App\Appointment\Event\AppointmentCompleted;
use App\Appointment\Service\AppointmentService;
use App\Appointment\Service\OverlapDetector;
use App\Appointment\Service\SlotCalculator;
use App\Appointment\ValueObject\TimeSlot;
use App\Common\Exception\ApiException;
use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\ShopService;
use App\Entity\User;
use App\Entity\WorkSchedule;
use App\Repository\AppointmentRepository;
use App\Repository\ClientRepository;
use App\Repository\ShopServiceRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Enum\DayOfWeek;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use App\Subscription\Service\AppointmentLimitChecker;
use App\Subscription\Service\SubscriptionService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(AppointmentService::class)]
final class AppointmentServiceTest extends TestCase
{
    private AppointmentRepository&MockObject $appointmentRepository;
    private ClientRepository&MockObject $clientRepository;
    private ShopServiceRepository&MockObject $shopServiceRepository;
    private WorkScheduleRepository&MockObject $workScheduleRepository;
    private OverlapDetector&MockObject $overlapDetector;
    private SlotCalculator&MockObject $slotCalculator;
    private EntityManagerInterface&MockObject $em;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private AppointmentLimitChecker&MockObject $appointmentLimitChecker;
    private SubscriptionService&MockObject $subscriptionService;
    private AppointmentService $sut;

    protected function setUp(): void
    {
        $this->appointmentRepository = $this->createMock(AppointmentRepository::class);
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->shopServiceRepository = $this->createMock(ShopServiceRepository::class);
        $this->workScheduleRepository = $this->createMock(WorkScheduleRepository::class);
        $this->overlapDetector = $this->createMock(OverlapDetector::class);
        $this->slotCalculator = $this->createMock(SlotCalculator::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->appointmentLimitChecker = $this->createMock(AppointmentLimitChecker::class);
        $this->subscriptionService = $this->createMock(SubscriptionService::class);

        $this->sut = new AppointmentService(
            $this->appointmentRepository,
            $this->clientRepository,
            $this->shopServiceRepository,
            $this->workScheduleRepository,
            $this->overlapDetector,
            $this->slotCalculator,
            $this->em,
            $this->eventDispatcher,
            $this->appointmentLimitChecker,
            $this->subscriptionService,
        );
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('barber@example.com');
        $user->setFirstName('Nguyen');
        $user->setLastName('Van');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    private function createClient(Shop $shop): Client
    {
        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName('John');
        $client->setLastName('Doe');
        $client->setPhone('0901234567');

        return $client;
    }

    private function createService(Shop $shop, int $durationMinutes = 30, int $price = 150000): ShopService
    {
        $service = new ShopService();
        $service->setShop($shop);
        $service->setName('Haircut');
        $service->setDurationMinutes($durationMinutes);
        $service->setPrice($price);
        $service->setIsActive(true);

        return $service;
    }

    private function createSchedule(Shop $shop, DayOfWeek $day, string $open = '08:00', string $close = '20:00'): WorkSchedule
    {
        $schedule = new WorkSchedule();
        $schedule->setShop($shop);
        $schedule->setDayOfWeek($day);
        $schedule->setIsOpen(true);
        $schedule->setOpenTime(new \DateTimeImmutable($open));
        $schedule->setCloseTime(new \DateTimeImmutable($close));

        return $schedule;
    }

    private function createAppointment(Shop $shop, ?Client $client = null, ?ShopService $service = null): Appointment
    {
        $appointment = new Appointment();
        $appointment->setShop($shop);
        $appointment->setClient($client ?? $this->createClient($shop));
        $svc = $service ?? $this->createService($shop);
        $appointment->setService($svc);
        $appointment->setPrice($svc->getPrice());
        $appointment->setStartTime(new \DateTimeImmutable('2026-03-16 02:00:00', new \DateTimeZone('UTC')));
        $appointment->setEndTime(new \DateTimeImmutable('2026-03-16 02:30:00', new \DateTimeZone('UTC')));

        return $appointment;
    }

    private function setupForCreate(Shop $shop, Client $client, ShopService $service, DayOfWeek $day): void
    {
        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);
        $this->overlapDetector->method('hasOverlap')->willReturn(false);
        $schedule = $this->createSchedule($shop, $day);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);
    }

    // --- create ---

    #[Test]
    public function testCreateSuccessful(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop, 30, 150000);
        // 2026-03-16 is Monday
        $this->setupForCreate($shop, $client, $service, DayOfWeek::MONDAY);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2026-03-16T09:00:00+07:00',
        );

        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $appointment = $this->sut->create($dto, $shop);

        self::assertSame($shop, $appointment->getShop());
        self::assertSame($client, $appointment->getClient());
        self::assertSame($service, $appointment->getService());
        self::assertSame(150000, $appointment->getPrice());
        self::assertSame(AppointmentStatus::SCHEDULED, $appointment->getStatus());
    }

    #[Test]
    public function testCreateCalculatesEndTimeFromServiceDuration(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop, 45, 200000);
        $this->setupForCreate($shop, $client, $service, DayOfWeek::MONDAY);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2026-03-16T09:00:00+07:00',
        );

        $appointment = $this->sut->create($dto, $shop);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        self::assertSame('09:45', $appointment->getEndTime()->setTimezone($tz)->format('H:i'));
    }

    #[Test]
    public function testCreateThrows404WhenClientNotFound(): void
    {
        $shop = $this->createShop();
        $this->clientRepository->method('findByShopAndId')->willReturn(null);

        $dto = new CreateAppointmentRequest(
            clientId: (string) Uuid::v7(),
            serviceId: (string) Uuid::v7(),
            startTime: '2026-03-16T09:00:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(404, $e->statusCode);
            self::assertSame('CLIENT_NOT_FOUND', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateThrows404WhenServiceNotFound(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->shopServiceRepository->method('findOneBy')->willReturn(null);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) Uuid::v7(),
            startTime: '2026-03-16T09:00:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(404, $e->statusCode);
            self::assertSame('SERVICE_NOT_FOUND', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateThrows400WhenServiceInactive(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop);
        $service->setIsActive(false);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2026-03-16T09:00:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('SERVICE_INACTIVE', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateThrows400WhenStartTimeNotAligned(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2026-03-16T09:15:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('INVALID_SLOT_ALIGNMENT', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateThrows422WhenTimeInPast(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2020-01-01T09:00:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(422, $e->statusCode);
            self::assertSame('TIME_IN_PAST', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateThrows422WhenShopClosed(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);

        $closedSchedule = new WorkSchedule();
        $closedSchedule->setShop($shop);
        $closedSchedule->setDayOfWeek(DayOfWeek::MONDAY);
        $closedSchedule->setIsOpen(false);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($closedSchedule);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2026-03-16T09:00:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(422, $e->statusCode);
            self::assertSame('SHOP_CLOSED', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateThrows422WhenOutsideWorkingHours(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);
        $this->overlapDetector->method('hasOverlap')->willReturn(false);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '17:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2026-03-16T07:00:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(422, $e->statusCode);
            self::assertSame('OUTSIDE_WORKING_HOURS', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateThrows409WhenOverlap(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);
        $this->overlapDetector->method('hasOverlap')->willReturn(true);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2026-03-16T09:00:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('APPOINTMENT_OVERLAP', $e->errorCode);
        }
    }

    // --- changeStatus ---

    #[Test]
    public function testChangeStatusScheduledToCompleted(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(AppointmentCompleted::class));

        $result = $this->sut->changeStatus($appointment, AppointmentStatus::COMPLETED);

        self::assertSame(AppointmentStatus::COMPLETED, $result->getStatus());
    }

    #[Test]
    public function testChangeStatusScheduledToCancelled(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $result = $this->sut->changeStatus($appointment, AppointmentStatus::CANCELLED);

        self::assertSame(AppointmentStatus::CANCELLED, $result->getStatus());
    }

    #[Test]
    public function testChangeStatusScheduledToNoShow(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);

        $result = $this->sut->changeStatus($appointment, AppointmentStatus::NO_SHOW);

        self::assertSame(AppointmentStatus::NO_SHOW, $result->getStatus());
    }

    #[Test]
    public function testChangeStatusThrows403FromTerminalState(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);
        $appointment->setStatus(AppointmentStatus::COMPLETED);

        try {
            $this->sut->changeStatus($appointment, AppointmentStatus::CANCELLED);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(403, $e->statusCode);
            self::assertSame('INVALID_STATUS_TRANSITION', $e->errorCode);
        }
    }

    // --- cancel ---

    #[Test]
    public function testCancelSetsStatusToCancelled(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);

        $result = $this->sut->cancel($appointment);

        self::assertSame(AppointmentStatus::CANCELLED, $result->getStatus());
    }

    #[Test]
    public function testCancelThrows403WhenAlreadyCompleted(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);
        $appointment->setStatus(AppointmentStatus::COMPLETED);

        try {
            $this->sut->cancel($appointment);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(403, $e->statusCode);
            self::assertSame('APPOINTMENT_NOT_MODIFIABLE', $e->errorCode);
        }
    }

    // --- update ---

    #[Test]
    public function testUpdateThrows403WhenTerminalStatus(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);
        $appointment->setStatus(AppointmentStatus::COMPLETED);

        $dto = new UpdateAppointmentRequest(notes: 'Updated');

        try {
            $this->sut->update($appointment, $dto, ['notes']);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(403, $e->statusCode);
            self::assertSame('APPOINTMENT_NOT_MODIFIABLE', $e->errorCode);
        }
    }

    #[Test]
    public function testUpdateNotesOnly(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);
        $originalStartTime = $appointment->getStartTime();

        $dto = new UpdateAppointmentRequest(notes: 'New note');
        $result = $this->sut->update($appointment, $dto, ['notes']);

        self::assertSame('New note', $result->getNotes());
        self::assertSame($originalStartTime, $result->getStartTime());
    }

    #[Test]
    public function testUpdateServiceRecalculatesEndTimeAndPrice(): void
    {
        $shop = $this->createShop();
        $originalService = $this->createService($shop, 30, 100000);
        $appointment = $this->createAppointment($shop, null, $originalService);

        $newService = $this->createService($shop, 60, 200000);
        $this->shopServiceRepository->method('findOneBy')->willReturn($newService);
        $this->overlapDetector->method('hasOverlap')->willReturn(false);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $dto = new UpdateAppointmentRequest(serviceId: (string) $newService->getId());
        $result = $this->sut->update($appointment, $dto, ['serviceId']);

        self::assertSame($newService, $result->getService());
        self::assertSame(200000, $result->getPrice());
        $diff = $result->getEndTime()->getTimestamp() - $result->getStartTime()->getTimestamp();
        self::assertSame(3600, $diff); // 60 minutes
    }

    #[Test]
    public function testUpdateClearsNotesWhenExplicitlyNull(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);
        $appointment->setNotes('Old notes');

        $dto = new UpdateAppointmentRequest();
        $result = $this->sut->update($appointment, $dto, ['notes']);

        self::assertNull($result->getNotes());
    }

    #[Test]
    public function testUpdateStartTimeRecalculatesEndTime(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30, 150000);
        $appointment = $this->createAppointment($shop, null, $service);

        $this->overlapDetector->method('hasOverlap')->willReturn(false);
        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $dto = new UpdateAppointmentRequest(startTime: '2026-03-16T14:00:00+07:00');
        $result = $this->sut->update($appointment, $dto, ['startTime']);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        self::assertSame('14:00', $result->getStartTime()->setTimezone($tz)->format('H:i'));
        self::assertSame('14:30', $result->getEndTime()->setTimezone($tz)->format('H:i'));
    }

    #[Test]
    public function testUpdateThrows409WhenOverlap(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);

        $this->overlapDetector->method('hasOverlap')->willReturn(true);
        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $dto = new UpdateAppointmentRequest(startTime: '2026-03-16T14:00:00+07:00');

        try {
            $this->sut->update($appointment, $dto, ['startTime']);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('APPOINTMENT_OVERLAP', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateCatchesDriverExceptionForConcurrentOverlap(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop);
        $this->setupForCreate($shop, $client, $service, DayOfWeek::MONDAY);

        $driverException = $this->createMock(DriverException::class);
        $driverException->method('getSQLState')->willReturn('23P01');
        $this->em->method('flush')->willThrowException($driverException);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2026-03-16T09:00:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('APPOINTMENT_OVERLAP', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateThrows422WhenNoScheduleExists(): void
    {
        $shop = $this->createShop();
        $client = $this->createClient($shop);
        $service = $this->createService($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->shopServiceRepository->method('findOneBy')->willReturn($service);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn(null);

        $dto = new CreateAppointmentRequest(
            clientId: (string) $client->getId(),
            serviceId: (string) $service->getId(),
            startTime: '2026-03-16T09:00:00+07:00',
        );

        try {
            $this->sut->create($dto, $shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(422, $e->statusCode);
            self::assertSame('SHOP_CLOSED', $e->errorCode);
        }
    }

    // --- getAvailableSlots ---

    #[Test]
    public function testGetAvailableSlotsThrowsWhenShopClosed(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop);
        $date = new \DateTimeImmutable('2026-03-22', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $closedSchedule = new WorkSchedule();
        $closedSchedule->setShop($shop);
        $closedSchedule->setDayOfWeek(DayOfWeek::SUNDAY);
        $closedSchedule->setIsOpen(false);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($closedSchedule);

        try {
            $this->sut->getAvailableSlots($shop, $date, $service);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('SHOP_CLOSED', $e->errorCode);
        }
    }

    #[Test]
    public function testGetAvailableSlotsReturnsCorrectStructure(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30);
        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-03-16 02:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-03-16 02:30:00', new \DateTimeZone('UTC')),
        );
        $this->slotCalculator->method('calculateAvailableSlots')->willReturn([$slot]);

        $result = $this->sut->getAvailableSlots($shop, $date, $service);

        self::assertSame('2026-03-16', $result['date']);
        self::assertSame(30, $result['serviceDurationMinutes']);
        self::assertCount(1, $result['slots']);
        self::assertArrayHasKey('startTime', $result['slots'][0]);
        self::assertArrayHasKey('endTime', $result['slots'][0]);
    }

    // --- getDailySchedule ---

    #[Test]
    public function testGetDailyScheduleReturnsWorkingHoursAndAppointments(): void
    {
        $shop = $this->createShop();
        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '18:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $appointment = $this->createAppointment($shop);
        $this->appointmentRepository->method('findByShopAndDate')->willReturn([$appointment]);

        $result = $this->sut->getDailySchedule($shop, $date);

        self::assertSame('2026-03-16', $result['date']);
        self::assertSame('09:00', $result['workingHours']['openTime']);
        self::assertSame('18:00', $result['workingHours']['closeTime']);
        self::assertCount(1, $result['appointments']);
        self::assertArrayHasKey('id', $result['appointments'][0]);
    }

    #[Test]
    public function testGetDailyScheduleClosedDayNullWorkingHours(): void
    {
        $shop = $this->createShop();
        $date = new \DateTimeImmutable('2026-03-22', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $closedSchedule = new WorkSchedule();
        $closedSchedule->setShop($shop);
        $closedSchedule->setDayOfWeek(DayOfWeek::SUNDAY);
        $closedSchedule->setIsOpen(false);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($closedSchedule);
        $this->appointmentRepository->method('findByShopAndDate')->willReturn([]);

        $result = $this->sut->getDailySchedule($shop, $date);

        self::assertNull($result['workingHours']);
        self::assertSame([], $result['appointments']);
    }

    // --- getRevenue ---

    #[Test]
    public function testGetRevenueThrows400WhenDateRangeExceeds90Days(): void
    {
        $shop = $this->createShop();
        $dateFrom = new \DateTimeImmutable('2026-01-01');
        $dateTo = new \DateTimeImmutable('2026-06-01');

        try {
            $this->sut->getRevenue($shop, $dateFrom, $dateTo);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('VALIDATION_ERROR', $e->errorCode);
        }
    }

    #[Test]
    public function testGetRevenueReturnsCorrectTotals(): void
    {
        $shop = $this->createShop();
        $dateFrom = new \DateTimeImmutable('2026-03-01', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $dateTo = new \DateTimeImmutable('2026-03-31', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $this->appointmentRepository->method('getRevenueByDay')->willReturn([
            ['date' => '2026-03-01', 'revenue' => 300000, 'count' => 2],
            ['date' => '2026-03-02', 'revenue' => 150000, 'count' => 1],
        ]);

        $result = $this->sut->getRevenue($shop, $dateFrom, $dateTo);

        self::assertSame(450000, $result['totalRevenue']);
        self::assertSame(3, $result['appointmentCount']);
        self::assertCount(2, $result['dailyBreakdown']);
    }

    // --- serializeAppointment ---

    #[Test]
    public function testSerializeAppointmentUsesSnapshotPrice(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30, 150000);
        $appointment = $this->createAppointment($shop, null, $service);
        $appointment->setPrice(100000); // snapshotted price differs from current service price

        $result = AppointmentService::serializeAppointment($appointment);

        self::assertSame(100000, $result['service']['price']);
    }

    #[Test]
    public function testSerializeAppointmentTimesInHoChiMinh(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);

        $result = AppointmentService::serializeAppointment($appointment);

        self::assertStringContainsString('+07:00', $result['startTime']);
        self::assertStringContainsString('+07:00', $result['endTime']);
        self::assertStringContainsString('+07:00', $result['createdAt']);
    }

    #[Test]
    public function testSerializeAppointmentContainsExpectedKeys(): void
    {
        $shop = $this->createShop();
        $appointment = $this->createAppointment($shop);
        $appointment->setNotes('Test notes');

        $result = AppointmentService::serializeAppointment($appointment);

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('client', $result);
        self::assertArrayHasKey('service', $result);
        self::assertArrayHasKey('startTime', $result);
        self::assertArrayHasKey('endTime', $result);
        self::assertArrayHasKey('status', $result);
        self::assertArrayHasKey('notes', $result);
        self::assertArrayHasKey('createdAt', $result);
        self::assertArrayHasKey('updatedAt', $result);
        self::assertSame('Test notes', $result['notes']);
        self::assertSame('scheduled', $result['status']);
    }
}
