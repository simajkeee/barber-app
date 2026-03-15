<?php

declare(strict_types=1);

namespace App\Appointment\Event;

use Symfony\Component\Uid\Uuid;

final readonly class AppointmentCompleted
{
    public function __construct(
        public Uuid $appointmentId,
    ) {
    }
}
