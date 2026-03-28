<?php

declare(strict_types=1);

namespace App\Reminder\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class OptOutRequest
{
    public function __construct(
        #[Assert\Regex(pattern: '/^[0-9a-f]{64}$/', message: 'Invalid token format.')]
        public string $token = '',
    ) {
    }
}
