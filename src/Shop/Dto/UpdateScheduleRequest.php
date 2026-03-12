<?php

declare(strict_types=1);

namespace App\Shop\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateScheduleRequest
{
    /**
     * @param ScheduleEntry[] $schedule
     */
    public function __construct(
        #[Assert\Count(exactly: 7, exactMessage: 'All 7 days of the week are required.')]
        #[Assert\Valid]
        public array $schedule = [],
    ) {
    }
}