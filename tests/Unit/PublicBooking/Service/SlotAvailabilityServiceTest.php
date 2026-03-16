<?php

declare(strict_types=1);

namespace App\Tests\Unit\PublicBooking\Service;

use App\Entity\Appointment;
use App\Entity\Shop;
use App\Entity\ShopService;
use App\Entity\User;
use App\Entity\WorkSchedule;
use App\PublicBooking\Service\SlotAvailabilityService;
use App\Repository\AppointmentRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Enum\DayOfWeek;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[CoversClass(SlotAvailabilityService::class)]
final class SlotAvailabilityServiceTest extends TestCase
{
    private AppointmentRepository&MockObject $appointmentRepository;
    private WorkScheduleRepository&MockObject $workScheduleRepository;
    private ClockInterface&MockObject $clock;
    private SlotAvailabilityService $sut;

    protected function setUp(): void
    {
        $this->appointmentRepository = $this->createMock(AppointmentRepository::class);
        $this->workScheduleRepository = $this->createMock(WorkScheduleRepository::class);
        $this->clock = $this->createMock(ClockInterface::class);

        $this->sut = new SlotAvailabilityService(
            $this->appointmentRepository,
            $this->workScheduleRepository,
            $this->clock,
        );
    }

    #[Test]
    public function getSlotsReturnsCorrectSlotsForWorkingDay(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = new \DateTimeImmutable('2026-03-16', $tz); // Monday

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '11:00');

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')
            ->willReturn([]);

        // Set clock to early morning so all slots are available
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 01:00:00', $tz));

        $slots = $this->sut->getSlots($shop, $date, $service);

        // 09:00-11:00 with 30min service: 09:00, 09:30, 10:00, 10:30
        self::assertCount(4, $slots);
        self::assertSame('09:00', $slots[0]['time']);
        self::assertTrue($slots[0]['available']);
        self::assertSame('09:30', $slots[1]['time']);
        self::assertSame('10:00', $slots[2]['time']);
        self::assertSame('10:30', $slots[3]['time']);
    }

    #[Test]
    public function getSlotsReturnsEmptyForClosedDay(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = new \DateTimeImmutable('2026-03-22', $tz); // Sunday

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn(null);

        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-22 01:00:00', $tz));

        $slots = $this->sut->getSlots($shop, $date, $service);

        self::assertSame([], $slots);
    }

    #[Test]
    public function getSlotsMarksUnavailableWhenOverlapExists(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = new \DateTimeImmutable('2026-03-16', $tz);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '11:00');

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn($schedule);

        // Existing appointment at 09:00-09:30 UTC+7 => 02:00-02:30 UTC
        $existingAppt = new Appointment();
        $existingAppt->setShop($shop);
        $existingAppt->setStartTime(new \DateTimeImmutable('2026-03-16 02:00:00', new \DateTimeZone('UTC')));
        $existingAppt->setEndTime(new \DateTimeImmutable('2026-03-16 02:30:00', new \DateTimeZone('UTC')));

        $this->appointmentRepository->method('findNonCancelledInRange')
            ->willReturn([$existingAppt]);

        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 01:00:00', $tz));

        $slots = $this->sut->getSlots($shop, $date, $service);

        self::assertCount(4, $slots);
        self::assertFalse($slots[0]['available']); // 09:00 overlaps
        self::assertTrue($slots[1]['available']);   // 09:30 is free
    }

    #[Test]
    public function getSlotsExcludesPastSlotsForToday(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = new \DateTimeImmutable('2026-03-16', $tz);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '11:00');

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')
            ->willReturn([]);

        // Current time is 09:30 — 09:00 is past, 09:30 is past
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 09:30:00', $tz));

        $slots = $this->sut->getSlots($shop, $date, $service);

        self::assertCount(4, $slots);
        self::assertFalse($slots[0]['available']); // 09:00 past
        self::assertFalse($slots[1]['available']); // 09:30 past (and within 1hr window)
        self::assertFalse($slots[2]['available']); // 10:00 within 1hr advance window
        self::assertTrue($slots[3]['available']);   // 10:30 available
    }

    #[Test]
    public function getSlotsExcludesSlotsWithinOneHourAdvanceWindow(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = new \DateTimeImmutable('2026-03-16', $tz);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '12:00');

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')
            ->willReturn([]);

        // Now is 09:30 — min advance cutoff is 10:30
        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 09:30:00', $tz));

        $slots = $this->sut->getSlots($shop, $date, $service);

        // 09:00 past, 09:30 past, 10:00 within window, 10:30 available, 11:00, 11:30
        self::assertFalse($slots[0]['available']); // 09:00
        self::assertFalse($slots[1]['available']); // 09:30
        self::assertFalse($slots[2]['available']); // 10:00
        self::assertTrue($slots[3]['available']);   // 10:30
        self::assertTrue($slots[4]['available']);   // 11:00
    }

    #[Test]
    public function getSlotsRespectsServiceDurationForLastSlot(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 60); // 60-min service
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = new \DateTimeImmutable('2026-03-16', $tz);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '11:00');

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')
            ->willReturn([]);

        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 01:00:00', $tz));

        $slots = $this->sut->getSlots($shop, $date, $service);

        // 60-min service, working hours 09:00-11:00
        // Possible: 09:00 (ends 10:00), 09:30 (ends 10:30), 10:00 (ends 11:00)
        // 10:30 (ends 11:30) would exceed close time
        self::assertCount(3, $slots);
        self::assertSame('09:00', $slots[0]['time']);
        self::assertSame('09:30', $slots[1]['time']);
        self::assertSame('10:00', $slots[2]['time']);
    }

    #[Test]
    public function getSlotsReturnsEmptyWhenScheduleIsNotOpen(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $date = new \DateTimeImmutable('2026-03-16', $tz);

        $schedule = new WorkSchedule();
        $schedule->setShop($shop);
        $schedule->setDayOfWeek(DayOfWeek::MONDAY);
        $schedule->setIsOpen(false);

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn($schedule);

        $this->clock->method('now')
            ->willReturn(new \DateTimeImmutable('2026-03-16 01:00:00', $tz));

        $slots = $this->sut->getSlots($shop, $date, $service);

        self::assertSame([], $slots);
    }

    #[Test]
    public function isSlotAvailableReturnsTrueForFreeSlot(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $dateTime = new \DateTimeImmutable('2026-03-16 10:00:00', $tz);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '18:00');

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn($schedule);

        $this->appointmentRepository->method('findNonCancelledInRange')
            ->willReturn([]);

        $result = $this->sut->isSlotAvailable($shop, $dateTime, $service);

        self::assertTrue($result);
    }

    #[Test]
    public function isSlotAvailableReturnsFalseForClosedDay(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 30);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $dateTime = new \DateTimeImmutable('2026-03-22 10:00:00', $tz);

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn(null);

        $result = $this->sut->isSlotAvailable($shop, $dateTime, $service);

        self::assertFalse($result);
    }

    #[Test]
    public function isSlotAvailableReturnsFalseWhenServiceExceedsClosingTime(): void
    {
        $shop = $this->createShop();
        $service = $this->createService($shop, 60);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $dateTime = new \DateTimeImmutable('2026-03-16 17:30:00', $tz);

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '18:00');

        $this->workScheduleRepository->method('findByShopAndDay')
            ->willReturn($schedule);

        $result = $this->sut->isSlotAvailable($shop, $dateTime, $service);

        self::assertFalse($result);
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

    private function createService(Shop $shop, int $duration): ShopService
    {
        $service = new ShopService();
        $service->setShop($shop);
        $service->setName('Haircut');
        $service->setDurationMinutes($duration);
        $service->setPrice(100000);

        return $service;
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
