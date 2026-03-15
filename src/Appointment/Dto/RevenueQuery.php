<?php

declare(strict_types=1);

namespace App\Appointment\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RevenueQuery
{
    public function __construct(
        #[Assert\NotBlank]
        public string $dateFrom = '',

        #[Assert\NotBlank]
        public string $dateTo = '',
    ) {
    }
}
