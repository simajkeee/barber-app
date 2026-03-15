<?php

declare(strict_types=1);

namespace App\Appointment\ValueObject;

final readonly class TimeSlot
{
    public function __construct(
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime,
    ) {
    }
}
