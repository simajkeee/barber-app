<?php

declare(strict_types=1);

namespace App\Client\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateClientRequest
{
    public function __construct(
        #[Assert\Length(min: 1, max: 100)]
        public ?string $firstName = null,

        #[Assert\Length(min: 1, max: 100)]
        public ?string $lastName = null,

        #[Assert\Length(max: 20)]
        public ?string $phone = null,

        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public ?string $email = null,

        #[Assert\Length(max: 2000)]
        public ?string $notes = null,
    ) {
    }
}
