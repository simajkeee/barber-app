<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Subscription;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Subscription::class)]
final class SubscriptionTest extends TestCase
{
    #[Test]
    public function testIsInTrialReturnsFalseWhenTrialEndsAtIsNull(): void
    {
        $subscription = new Subscription();

        self::assertFalse($subscription->isInTrial());
    }

    #[Test]
    public function testIsInTrialReturnsTrueWhenTrialEndsAtIsInFuture(): void
    {
        $subscription = new Subscription();
        $subscription->setTrialEndsAt(new \DateTimeImmutable('+10 days'));

        self::assertTrue($subscription->isInTrial());
    }

    #[Test]
    public function testIsInTrialReturnsFalseWhenTrialEndsAtIsInPast(): void
    {
        $subscription = new Subscription();
        $subscription->setTrialEndsAt(new \DateTimeImmutable('-1 day'));

        self::assertFalse($subscription->isInTrial());
    }
}
