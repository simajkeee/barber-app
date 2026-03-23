<?php

declare(strict_types=1);

namespace App\Subscription\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUpgradeRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public string $name = '',

        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = '',

        #[Assert\Length(min: 7, max: 20)]
        public ?string $phone = null,

        #[Assert\Length(max: 500)]
        public ?string $message = null,
    ) {
    }
}
