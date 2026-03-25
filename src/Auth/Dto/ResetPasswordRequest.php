<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ResetPasswordRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(exactly: 64)]
        public string $token = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 255)]
        public string $password = '',
    ) {
    }
}
