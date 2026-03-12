<?php

declare(strict_types=1);

namespace App\Shop\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateServiceRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',

        #[Assert\NotNull]
        #[Assert\Range(min: 5, max: 480)]
        public int $durationMinutes = 0,

        #[Assert\NotNull]
        #[Assert\Range(min: 1000)]
        public int $price = 0,

        public int $sortOrder = 0,
    ) {
    }
}