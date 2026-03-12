<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Auth\Enum\UserLocale;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 255)]
        public string $password = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $firstName = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $lastName = '',

        public UserLocale $locale = UserLocale::VI,
    ) {
    }
}