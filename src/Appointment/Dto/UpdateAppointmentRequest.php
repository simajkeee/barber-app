<?php

declare(strict_types=1);

namespace App\Appointment\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAppointmentRequest
{
    public function __construct(
        #[Assert\Uuid]
        public ?string $clientId = null,

        #[Assert\Uuid]
        public ?string $serviceId = null,

        public ?string $startTime = null,

        #[Assert\Length(max: 1000)]
        public ?string $notes = null,
    ) {
    }
}
