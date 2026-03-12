<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Util;

use App\Client\Util\PhoneNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhoneNormalizer::class)]
final class PhoneNormalizerTest extends TestCase
{
    // --- normalize ---

    #[Test]
    #[DataProvider('normalizeProvider')]
    public function testNormalize(string $input, string $expected): void
    {
        self::assertSame($expected, PhoneNormalizer::normalize($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizeProvider(): iterable
    {
        yield 'already clean' => ['+84901234567', '+84901234567'];
        yield 'spaces' => ['+84 901 234 567', '+84901234567'];
        yield 'dashes' => ['+84-901-234-567', '+84901234567'];
        yield 'parentheses' => ['(090) 123 4567', '0901234567'];
        yield 'mixed formatting' => ['+1 (555) 123-4567', '+15551234567'];
        yield 'empty string' => ['', ''];
        yield 'only spaces and dashes' => ['  -- () ', ''];
    }

    // --- isValid ---

    #[Test]
    #[DataProvider('validPhoneProvider')]
    public function testIsValidReturnsTrueForValidPhones(string $phone): void
    {
        self::assertTrue(PhoneNormalizer::isValid($phone));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validPhoneProvider(): iterable
    {
        yield '7 digits (minimum)' => ['1234567'];
        yield '15 digits (maximum)' => ['123456789012345'];
        yield 'with plus prefix' => ['+84901234567'];
        yield '10 digits' => ['0901234567'];
    }

    #[Test]
    #[DataProvider('invalidPhoneProvider')]
    public function testIsValidReturnsFalseForInvalidPhones(string $phone): void
    {
        self::assertFalse(PhoneNormalizer::isValid($phone));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPhoneProvider(): iterable
    {
        yield 'empty string' => [''];
        yield '6 digits (too short)' => ['123456'];
        yield '16 digits (too long)' => ['1234567890123456'];
        yield 'contains letters' => ['090abc1234'];
        yield 'plus in middle' => ['090+1234567'];
        yield 'special characters' => ['090!@#4567'];
    }
}