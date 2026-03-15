<?php

declare(strict_types=1);

namespace App\Appointment\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeStatusRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['completed', 'cancelled', 'no_show'])]
        public string $status = '',
    ) {
    }
}
