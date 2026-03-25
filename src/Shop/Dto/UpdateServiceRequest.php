<?php

declare(strict_types=1);

namespace App\Shop\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateServiceRequest
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $name = null,

        #[Assert\Range(min: 5, max: 480)]
        public ?int $durationMinutes = null,

        #[Assert\Range(min: 1000)]
        public ?int $price = null,

        public ?bool $isActive = null,

        public ?int $sortOrder = null,
    ) {
    }
}
