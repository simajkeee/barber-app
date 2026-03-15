<?php

declare(strict_types=1);

namespace App\Tests\Unit\Appointment\Service;

use App\Appointment\Service\SlotCalculator;
use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\ShopService;
use App\Entity\User;
use App\Entity\WorkSchedule;
use App\Repository\AppointmentRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Enum\DayOfWeek;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

#[CoversClass(SlotCalculator::class)]
final class SlotCalculatorTest extends TestCase
{
    private AppointmentRepository&MockObject $appointmentRepository;
    private WorkScheduleRepository&MockObject $workScheduleRepository;
    private MockClock $clock;
    private SlotCalculator $sut;

    protected function setUp(): void
    {
        $this->appointmentRepository = $this->createMock(AppointmentRepository::class);
        $this->workScheduleRepository = $this->createMock(WorkScheduleRepository::class);
        $this->clock = new MockClock(new \DateTimeImmutable('2026-03-14 00:00:00', new \DateTimeZone('UTC')));
        $this->sut = new SlotCalculator($this->appointmentRepository, $this->workScheduleRepository, $this->clock);
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

    private function createSchedule(Shop $shop, DayOfWeek $day, string $open, string $close): WorkSchedule
    {
        $schedule = new WorkSchedule();
        $schedule->setShop($shop);
        $schedule->setDayOfWeek($day);
        $schedule->setIsOpen(true);
        $schedule->setOpenTime(new \DateTimeImmutable($open));
        $schedule->setCloseTime(new \DateTimeImmutable($close));

        return $schedule;
    }

    private function createClosedSchedule(Shop $shop, DayOfWeek $day): WorkSchedule
    {
        $schedule = new WorkSchedule();
        $schedule->setShop($shop);
        $schedule->setDayOfWeek($day);
        $schedule->setIsOpen(false);

        return $schedule;
    }

    private function createAppointment(Shop $shop, string $start, string $end): Appointment
    {
        $appointment = new Appointment();
        $appointment->setShop($shop);
        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName('Test');
        $client->setLastName('Client');
        $client->setPhone('0901234567');
        $appointment->setClient($client);
        $service = new ShopService();
        $service->setShop($shop);
        $service->setName('Haircut');
        $service->setDurationMinutes(30);
        $service->setPrice(100000);
        $appointment->setService($service);
        $appointment->setPrice(100000);
        $appointment->setStartTime(new \DateTimeImmutable($start, new \DateTimeZone('UTC')));
        $appointment->setEndTime(new \DateTimeImmutable($end, new \DateTimeZone('UTC')));

        return $appointment;
    }

    #[Test]
    public function testNormalDayNoAppointmentsReturnsAllSlots(): void
    {
        $shop = $this->createShop();
        // 2026-03-16 is a Monday
        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '08:00', '10:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);
        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([]);

        $slots = $this->sut->calculateAvailableSlots($shop, $date, 30);

        self::assertCount(4, $slots);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        self::assertSame('08:00', $slots[0]->startTime->setTimezone($tz)->format('H:i'));
        self::assertSame('08:30', $slots[1]->startTime->setTimezone($tz)->format('H:i'));
        self::assertSame('09:00', $slots[2]->startTime->setTimezone($tz)->format('H:i'));
        self::assertSame('09:30', $slots[3]->startTime->setTimezone($tz)->format('H:i'));
    }

    #[Test]
    public function testSlotBlockedByExistingAppointment(): void
    {
        $shop = $this->createShop();
        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '11:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $appointment = $this->createAppointment($shop, '2026-03-16 03:00:00', '2026-03-16 03:30:00');
        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([$appointment]);

        $slots = $this->sut->calculateAvailableSlots($shop, $date, 30);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $startTimes = array_map(fn ($s) => $s->startTime->setTimezone($tz)->format('H:i'), $slots);

        self::assertContains('09:00', $startTimes);
        self::assertContains('09:30', $startTimes);
        self::assertNotContains('10:00', $startTimes);
        self::assertContains('10:30', $startTimes);
    }

    #[Test]
    public function testLongerServiceBlocksMoreSlots(): void
    {
        $shop = $this->createShop();
        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '11:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $appointment = $this->createAppointment($shop, '2026-03-16 03:00:00', '2026-03-16 03:30:00');
        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([$appointment]);

        $slots = $this->sut->calculateAvailableSlots($shop, $date, 60);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $startTimes = array_map(fn ($s) => $s->startTime->setTimezone($tz)->format('H:i'), $slots);

        self::assertContains('09:00', $startTimes);
        self::assertNotContains('09:30', $startTimes);
        self::assertNotContains('10:00', $startTimes);
    }

    #[Test]
    public function testClosedDayReturnsNoSlots(): void
    {
        $shop = $this->createShop();
        $date = new \DateTimeImmutable('2026-03-22', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createClosedSchedule($shop, DayOfWeek::SUNDAY);
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $slots = $this->sut->calculateAvailableSlots($shop, $date, 30);

        self::assertSame([], $slots);
    }

    #[Test]
    public function testNoScheduleReturnsNoSlots(): void
    {
        $shop = $this->createShop();
        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $this->workScheduleRepository->method('findByShopAndDay')->willReturn(null);

        $slots = $this->sut->calculateAvailableSlots($shop, $date, 30);

        self::assertSame([], $slots);
    }

    #[Test]
    public function testLastSlotFitsBeforeClosing(): void
    {
        $shop = $this->createShop();
        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '19:00', '20:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);
        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([]);

        $slots = $this->sut->calculateAvailableSlots($shop, $date, 30);

        self::assertCount(2, $slots);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        self::assertSame('19:30', $slots[1]->startTime->setTimezone($tz)->format('H:i'));
        self::assertSame('20:00', $slots[1]->endTime->setTimezone($tz)->format('H:i'));
    }

    #[Test]
    public function testAdjacentAppointmentsNoOverlap(): void
    {
        $shop = $this->createShop();
        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '11:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);

        $appt1 = $this->createAppointment($shop, '2026-03-16 02:00:00', '2026-03-16 02:30:00');
        $appt2 = $this->createAppointment($shop, '2026-03-16 03:00:00', '2026-03-16 03:30:00');
        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([$appt1, $appt2]);

        $slots = $this->sut->calculateAvailableSlots($shop, $date, 30);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $startTimes = array_map(fn ($s) => $s->startTime->setTimezone($tz)->format('H:i'), $slots);

        self::assertContains('09:30', $startTimes);
        self::assertContains('10:30', $startTimes);
        self::assertNotContains('09:00', $startTimes);
        self::assertNotContains('10:00', $startTimes);
    }

    #[Test]
    public function testTodayExcludesPastSlots(): void
    {
        $shop = $this->createShop();
        // Set clock to 2026-03-16 10:15 Ho Chi Minh (03:15 UTC)
        $this->clock->modify('2026-03-16 03:15:00 UTC');

        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '12:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);
        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([]);

        $slots = $this->sut->calculateAvailableSlots($shop, $date, 30);

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $startTimes = array_map(fn ($s) => $s->startTime->setTimezone($tz)->format('H:i'), $slots);

        // Slots before 10:15 should be excluded
        self::assertNotContains('09:00', $startTimes);
        self::assertNotContains('09:30', $startTimes);
        self::assertNotContains('10:00', $startTimes);
        // 10:30 and later should be available
        self::assertContains('10:30', $startTimes);
        self::assertContains('11:00', $startTimes);
        self::assertContains('11:30', $startTimes);
    }

    #[Test]
    public function testAllAppointmentsCancelledAllSlotsAvailable(): void
    {
        $shop = $this->createShop();
        $date = new \DateTimeImmutable('2026-03-16', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $schedule = $this->createSchedule($shop, DayOfWeek::MONDAY, '09:00', '10:00');
        $this->workScheduleRepository->method('findByShopAndDay')->willReturn($schedule);
        // findNonCancelledInRange returns empty because cancelled appointments are excluded
        $this->appointmentRepository->method('findNonCancelledInRange')->willReturn([]);

        $slots = $this->sut->calculateAvailableSlots($shop, $date, 30);

        self::assertCount(2, $slots);
    }
}
