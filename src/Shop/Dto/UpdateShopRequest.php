<?php

declare(strict_types=1);

namespace App\Shop\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateShopRequest
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255)]
        public ?string $name = null,

        #[Assert\Length(min: 1, max: 500)]
        public ?string $address = null,

        #[Assert\Length(min: 1, max: 20)]
        public ?string $phone = null,

        #[Assert\Length(max: 2000)]
        public ?string $description = null,

        #[Assert\Regex(
            pattern: '/^[a-z0-9][a-z0-9-]{2,97}[a-z0-9]$/',
            message: 'Slug must be 4-100 characters, lowercase alphanumeric and hyphens, no leading/trailing hyphens.',
        )]
        public ?string $slug = null,

        #[Assert\Length(max: 500)]
        public ?string $coverImageUrl = null,
    ) {
    }
}
