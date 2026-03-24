<?php

declare(strict_types=1);

namespace App\Common\Service;

interface CaptchaValidatorInterface
{
    public function validate(string $token): bool;
}
