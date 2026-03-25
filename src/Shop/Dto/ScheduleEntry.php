<?php

declare(strict_types=1);

namespace App\Shop\Dto;

use App\Shop\Enum\DayOfWeek;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ScheduleEntry
{
    public function __construct(
        #[Assert\NotNull]
        public ?DayOfWeek $dayOfWeek = null,

        #[Assert\Regex(pattern: '/^([01]\d|2[0-3]):[0-5]\d$/')]
        public ?string $openTime = null,

        #[Assert\Regex(pattern: '/^([01]\d|2[0-3]):[0-5]\d$/')]
        public ?string $closeTime = null,

        #[Assert\NotNull]
        public bool $isOpen = true,
    ) {
    }
}
