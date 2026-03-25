<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RefreshTokenRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $refreshToken = '',
    ) {
    }
}
