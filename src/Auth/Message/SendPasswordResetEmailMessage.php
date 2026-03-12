<?php

declare(strict_types=1);

namespace App\Auth\Message;

use App\Message\AsyncMessageInterface;

final readonly class SendPasswordResetEmailMessage implements AsyncMessageInterface
{
    public function __construct(
        public string $email,
        public string $rawToken,
        public string $locale,
    ) {
    }
}