<?php

declare(strict_types=1);

namespace App\Appointment\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAppointmentRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $clientId = '',

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $serviceId = '',

        #[Assert\NotBlank]
        public string $startTime = '',

        #[Assert\Length(max: 1000)]
        public ?string $notes = null,
    ) {
    }
}
