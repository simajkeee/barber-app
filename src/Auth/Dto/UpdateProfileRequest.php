<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Auth\Enum\UserLocale;
use Symfony\Component\Validator\Constraints as Assert;

// phoneNumber is intentionally excluded: it cannot be changed after registration
// to prevent trial reset abuse (registering a new account with the same phone).
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
