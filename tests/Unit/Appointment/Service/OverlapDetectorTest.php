<?php

declare(strict_types=1);

namespace App\Tests\Unit\Appointment\Service;

use App\Appointment\Service\OverlapDetector;
use App\Entity\Appointment;
use App\Entity\Shop;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(OverlapDetector::class)]
final class OverlapDetectorTest extends TestCase
{
    private AppointmentRepository&MockObject $appointmentRepository;
    private OverlapDetector $sut;

    protected function setUp(): void
    {
        $this->appointmentRepository = $this->createMock(AppointmentRepository::class);
        $this->sut = new OverlapDetector($this->appointmentRepository);
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

    #[Test]
    public function testOverlappingAppointmentsDetected(): void
    {
        $shop = $this->createShop();
        $start = new \DateTimeImmutable('2026-03-15 10:00:00', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable('2026-03-15 10:30:00', new \DateTimeZone('UTC'));

        $this->appointmentRepository->expects(self::once())
            ->method('findNonCancelledInRange')
            ->with($shop, $start, $end, null)
            ->willReturn([new Appointment()]);

        self::assertTrue($this->sut->hasOverlap($shop, $start, $end));
    }

    #[Test]
    public function testNoOverlapWhenNoConflicts(): void
    {
        $shop = $this->createShop();
        $start = new \DateTimeImmutable('2026-03-15 10:00:00', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable('2026-03-15 10:30:00', new \DateTimeZone('UTC'));

        $this->appointmentRepository->expects(self::once())
            ->method('findNonCancelledInRange')
            ->willReturn([]);

        self::assertFalse($this->sut->hasOverlap($shop, $start, $end));
    }

    #[Test]
    public function testAdjacentAppointmentsNoOverlapHalfOpenInterval(): void
    {
        $shop = $this->createShop();
        // Check slot 10:30-11:00 when existing appointment is 10:00-10:30
        // The query uses start_time < endTime AND end_time > startTime (half-open interval)
        // Adjacent: existing.endTime == new.startTime -> repository returns empty (no overlap)
        $start = new \DateTimeImmutable('2026-03-15 10:30:00', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable('2026-03-15 11:00:00', new \DateTimeZone('UTC'));

        $this->appointmentRepository->expects(self::once())
            ->method('findNonCancelledInRange')
            ->with($shop, $start, $end, null)
            ->willReturn([]);

        self::assertFalse($this->sut->hasOverlap($shop, $start, $end));
    }

    #[Test]
    public function testExactSameTimeRangeDetected(): void
    {
        $shop = $this->createShop();
        $start = new \DateTimeImmutable('2026-03-15 10:00:00', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable('2026-03-15 10:30:00', new \DateTimeZone('UTC'));

        $this->appointmentRepository->expects(self::once())
            ->method('findNonCancelledInRange')
            ->with($shop, $start, $end, null)
            ->willReturn([new Appointment()]);

        self::assertTrue($this->sut->hasOverlap($shop, $start, $end));
    }

    #[Test]
    public function testPartialOverlapDetected(): void
    {
        $shop = $this->createShop();
        // New slot 10:15-10:45, existing appointment 10:00-10:30 -> overlap
        $start = new \DateTimeImmutable('2026-03-15 10:15:00', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable('2026-03-15 10:45:00', new \DateTimeZone('UTC'));

        $this->appointmentRepository->expects(self::once())
            ->method('findNonCancelledInRange')
            ->with($shop, $start, $end, null)
            ->willReturn([new Appointment()]);

        self::assertTrue($this->sut->hasOverlap($shop, $start, $end));
    }

    #[Test]
    public function testCancelledAppointmentNoOverlap(): void
    {
        $shop = $this->createShop();
        // Cancelled appointments are excluded by findNonCancelledInRange, so it returns empty
        $start = new \DateTimeImmutable('2026-03-15 10:00:00', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable('2026-03-15 10:30:00', new \DateTimeZone('UTC'));

        $this->appointmentRepository->expects(self::once())
            ->method('findNonCancelledInRange')
            ->with($shop, $start, $end, null)
            ->willReturn([]);

        self::assertFalse($this->sut->hasOverlap($shop, $start, $end));
    }

    #[Test]
    public function testOverlapCheckExcludesSpecifiedAppointment(): void
    {
        $shop = $this->createShop();
        $excludeId = Uuid::v7();
        $start = new \DateTimeImmutable('2026-03-15 10:00:00', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable('2026-03-15 10:30:00', new \DateTimeZone('UTC'));

        $this->appointmentRepository->expects(self::once())
            ->method('findNonCancelledInRange')
            ->with($shop, $start, $end, $excludeId)
            ->willReturn([]);

        self::assertFalse($this->sut->hasOverlap($shop, $start, $end, $excludeId));
    }
}
