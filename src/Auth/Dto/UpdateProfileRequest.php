<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Auth\Enum\UserLocale;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProfileRequest
{
    public function __construct(
        #[Assert\Length(max: 100)]
        public ?string $firstName = null,

        #[Assert\Length(max: 100)]
        public ?string $lastName = null,

        public ?UserLocale $locale = null,
    ) {
    }
}