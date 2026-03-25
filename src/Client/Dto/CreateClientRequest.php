<?php

declare(strict_types=1);

namespace App\Client\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateClientRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $firstName = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $lastName = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        public string $phone = '',

        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public ?string $email = null,

        #[Assert\Length(max: 2000)]
        public ?string $notes = null,
    ) {
    }
}
