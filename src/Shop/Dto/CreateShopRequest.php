<?php

declare(strict_types=1);

namespace App\Shop\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateShopRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public string $address = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        public string $phone = '',

        #[Assert\Length(max: 2000)]
        public ?string $description = null,
    ) {
    }
}
