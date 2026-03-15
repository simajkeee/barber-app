<?php

declare(strict_types=1);

namespace App\Tests\Unit\Appointment\Enum;

use App\Appointment\Enum\AppointmentStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppointmentStatus::class)]
final class AppointmentStatusTest extends TestCase
{
    #[Test]
    public function testScheduledIsNotTerminal(): void
    {
        self::assertFalse(AppointmentStatus::SCHEDULED->isTerminal());
    }

    #[Test]
    public function testCompletedIsTerminal(): void
    {
        self::assertTrue(AppointmentStatus::COMPLETED->isTerminal());
    }

    #[Test]
    public function testCancelledIsTerminal(): void
    {
        self::assertTrue(AppointmentStatus::CANCELLED->isTerminal());
    }

    #[Test]
    public function testNoShowIsTerminal(): void
    {
        self::assertTrue(AppointmentStatus::NO_SHOW->isTerminal());
    }

    #[Test]
    public function testScheduledCanTransitionToCompleted(): void
    {
        self::assertTrue(AppointmentStatus::SCHEDULED->canTransitionTo(AppointmentStatus::COMPLETED));
    }

    #[Test]
    public function testScheduledCanTransitionToCancelled(): void
    {
        self::assertTrue(AppointmentStatus::SCHEDULED->canTransitionTo(AppointmentStatus::CANCELLED));
    }

    #[Test]
    public function testScheduledCanTransitionToNoShow(): void
    {
        self::assertTrue(AppointmentStatus::SCHEDULED->canTransitionTo(AppointmentStatus::NO_SHOW));
    }

    #[Test]
    public function testScheduledCannotTransitionToScheduled(): void
    {
        self::assertFalse(AppointmentStatus::SCHEDULED->canTransitionTo(AppointmentStatus::SCHEDULED));
    }

    #[Test]
    public function testCompletedCannotTransitionToAnything(): void
    {
        self::assertFalse(AppointmentStatus::COMPLETED->canTransitionTo(AppointmentStatus::CANCELLED));
        self::assertFalse(AppointmentStatus::COMPLETED->canTransitionTo(AppointmentStatus::SCHEDULED));
        self::assertFalse(AppointmentStatus::COMPLETED->canTransitionTo(AppointmentStatus::NO_SHOW));
    }

    #[Test]
    public function testCancelledCannotTransitionToAnything(): void
    {
        self::assertFalse(AppointmentStatus::CANCELLED->canTransitionTo(AppointmentStatus::SCHEDULED));
        self::assertFalse(AppointmentStatus::CANCELLED->canTransitionTo(AppointmentStatus::COMPLETED));
        self::assertFalse(AppointmentStatus::CANCELLED->canTransitionTo(AppointmentStatus::NO_SHOW));
    }

    #[Test]
    public function testNoShowCannotTransitionToAnything(): void
    {
        self::assertFalse(AppointmentStatus::NO_SHOW->canTransitionTo(AppointmentStatus::SCHEDULED));
        self::assertFalse(AppointmentStatus::NO_SHOW->canTransitionTo(AppointmentStatus::COMPLETED));
        self::assertFalse(AppointmentStatus::NO_SHOW->canTransitionTo(AppointmentStatus::CANCELLED));
    }
}
