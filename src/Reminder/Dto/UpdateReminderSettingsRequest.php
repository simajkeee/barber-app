<?php

declare(strict_types=1);

namespace App\Reminder\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateReminderSettingsRequest
{
    public function __construct(
        #[Assert\Range(min: 1, max: 365)]
        public ?int $daysSinceLastVisit = null,

        #[Assert\Length(max: 1000)]
        public ?string $messageTemplate = null,

        #[Assert\Choice(choices: ['vi', 'en'])]
        public ?string $locale = null,
    ) {
    }
}
