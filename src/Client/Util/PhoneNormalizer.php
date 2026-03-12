<?php

declare(strict_types=1);

namespace App\Client\Util;

final class PhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        return preg_replace('/[\s\-\(\)]/', '', $phone);
    }

    public static function isValid(string $normalizedPhone): bool
    {
        return preg_match('/^\+?[0-9]{7,15}$/', $normalizedPhone) === 1;
    }
}