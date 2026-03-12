<?php

declare(strict_types=1);

namespace App\Common\Exception;

final class ApiException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $statusCode = 400,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }
}