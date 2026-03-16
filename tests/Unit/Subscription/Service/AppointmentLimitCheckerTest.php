<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Service;

use App\Common\Exception\ApiException;
use App\Entity\Shop;
use App\Entity\User;
use App\Subscription\Service\AppointmentLimitChecker;
use App\Subscription\Service\SubscriptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppointmentLimitChecker::class)]
final class AppointmentLimitCheckerTest extends TestCase
{
    private SubscriptionService&MockObject $subscriptionService;
    private AppointmentLimitChecker $sut;

    protected function setUp(): void
    {
        $this->subscriptionService = $this->createMock(SubscriptionService::class);
        $this->sut = new AppointmentLimitChecker($this->subscriptionService);
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    #[Test]
    public function testCheckPassesWhenCanCreateAppointment(): void
    {
        $shop = $this->createShop();
        $this->subscriptionService->method('canCreateAppointment')->with($shop)->willReturn(true);

        $this->sut->check($shop);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testCheckThrows403WhenLimitReached(): void
    {
        $shop = $this->createShop();
        $this->subscriptionService->method('canCreateAppointment')->with($shop)->willReturn(false);

        try {
            $this->sut->check($shop);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(403, $e->statusCode);
            self::assertSame('APPOINTMENT_LIMIT_REACHED', $e->errorCode);
        }
    }
}
