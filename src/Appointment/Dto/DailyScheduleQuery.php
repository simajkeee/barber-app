<?php

declare(strict_types=1);

namespace App\Appointment\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DailyScheduleQuery
{
    public function __construct(
        #[Assert\NotBlank]
        public string $date = '',
    ) {
    }
}
