<?php

declare(strict_types=1);

namespace App\PublicBooking\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BookingRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $clientName = '',

        #[Assert\NotBlank]
        #[Assert\Regex(
            pattern: '/^(0|\+84)[0-9]{9}$/',
            message: 'Invalid Vietnamese phone number format.',
        )]
        public string $clientPhone = '',

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $serviceId = '',

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d{4}-\d{2}-\d{2}$/')]
        public string $date = '',

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d{2}:\d{2}$/')]
        public string $time = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 2048)]
        public string $captchaToken = '',
    ) {
    }
}
