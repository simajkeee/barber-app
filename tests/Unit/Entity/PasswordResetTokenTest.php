<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordResetToken::class)]
final class PasswordResetTokenTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('A');
        $user->setLastName('B');

        return $user;
    }

    #[Test]
    public function testNewTokenIsNotUsed(): void
    {
        $token = new PasswordResetToken($this->createUser(), 'hash', new \DateTimeImmutable('+1 hour'));

        self::assertFalse($token->isUsed());
        self::assertNull($token->getUsedAt());
    }

    #[Test]
    public function testMarkUsedSetsUsedAt(): void
    {
        $token = new PasswordResetToken($this->createUser(), 'hash', new \DateTimeImmutable('+1 hour'));

        $token->markUsed();

        self::assertTrue($token->isUsed());
        self::assertNotNull($token->getUsedAt());
    }

    #[Test]
    public function testTokenWithFutureExpiryIsNotExpired(): void
    {
        $token = new PasswordResetToken($this->createUser(), 'hash', new \DateTimeImmutable('+1 hour'));

        self::assertFalse($token->isExpired());
    }

    #[Test]
    public function testTokenWithPastExpiryIsExpired(): void
    {
        $token = new PasswordResetToken($this->createUser(), 'hash', new \DateTimeImmutable('-1 minute'));

        self::assertTrue($token->isExpired());
    }

    #[Test]
    public function testConstructorSetsProperties(): void
    {
        $user = $this->createUser();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $token = new PasswordResetToken($user, 'myhash', $expiresAt);

        self::assertSame($user, $token->getUser());
        self::assertSame('myhash', $token->getTokenHash());
        self::assertSame($expiresAt, $token->getExpiresAt());
        self::assertNotNull($token->getId());
        self::assertNotNull($token->getCreatedAt());
    }
}