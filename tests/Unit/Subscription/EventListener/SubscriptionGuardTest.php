<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\EventListener;

use App\Entity\Shop;
use App\Entity\Subscription;
use App\Entity\User;
use App\Subscription\Enum\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionStatus;
use App\Subscription\EventListener\SubscriptionGuard;
use App\Subscription\Service\SubscriptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

#[CoversClass(SubscriptionGuard::class)]
final class SubscriptionGuardTest extends TestCase
{
    private SubscriptionService&MockObject $subscriptionService;
    private TokenStorageInterface&MockObject $tokenStorage;
    private SubscriptionGuard $sut;

    protected function setUp(): void
    {
        $this->subscriptionService = $this->createMock(SubscriptionService::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->sut = new SubscriptionGuard(
            $this->subscriptionService,
            $this->tokenStorage,
        );
    }

    private function createRequestEvent(string $method, string $uri, bool $isMainRequest = true): RequestEvent
    {
        $request = Request::create($uri, $method);
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent(
            $kernel,
            $request,
            $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
        );
    }

    private function createUserWithShop(): array
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

        // Use reflection to set the inverse side of the OneToOne relationship
        $ref = new \ReflectionProperty(User::class, 'shop');
        $ref->setValue($user, $shop);

        return [$user, $shop];
    }

    private function setupAuthentication(User $user): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);
    }

    private function createSubscription(
        Shop $shop,
        SubscriptionStatus $status = SubscriptionStatus::ACTIVE,
    ): Subscription {
        $subscription = new Subscription();
        $subscription->setShop($shop);
        $subscription->setPlan(SubscriptionPlan::FREE);
        $subscription->setStatus($status);
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setCountResetAt(new \DateTimeImmutable());

        return $subscription;
    }

    #[Test]
    public function testAllowsGetRequestsRegardlessOfStatus(): void
    {
        [$user, $shop] = $this->createUserWithShop();
        $this->setupAuthentication($user);

        $subscription = $this->createSubscription($shop, SubscriptionStatus::CANCELLED);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $event = $this->createRequestEvent('GET', '/api/v1/clients');

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function testBlocksWritesWhenCancelled(): void
    {
        [$user, $shop] = $this->createUserWithShop();
        $this->setupAuthentication($user);

        $subscription = $this->createSubscription($shop, SubscriptionStatus::CANCELLED);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $event = $this->createRequestEvent('POST', '/api/v1/clients');

        $this->sut->__invoke($event);

        self::assertNotNull($event->getResponse());
        self::assertSame(403, $event->getResponse()->getStatusCode());

        $content = json_decode($event->getResponse()->getContent(), true);
        self::assertSame('SUBSCRIPTION_CANCELLED', $content['code']);
    }

    #[Test]
    public function testAllowsWritesForActiveSubscription(): void
    {
        [$user, $shop] = $this->createUserWithShop();
        $this->setupAuthentication($user);

        $subscription = $this->createSubscription($shop, SubscriptionStatus::ACTIVE);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);

        $event = $this->createRequestEvent('POST', '/api/v1/clients');

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function testSkipsAuthRoutes(): void
    {
        $event = $this->createRequestEvent('POST', '/api/v1/auth/login');

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function testSkipsAdminRoutes(): void
    {
        $event = $this->createRequestEvent('POST', '/api/v1/admin/subscriptions/123/activate');

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function testSkipsSubscriptionRoutes(): void
    {
        $event = $this->createRequestEvent('POST', '/api/v1/subscription');

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function testSkipsPublicRoutes(): void
    {
        $event = $this->createRequestEvent('POST', '/api/v1/public/shops/123/slots');

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function testSkipsNonApiRoutes(): void
    {
        $event = $this->createRequestEvent('POST', '/some/other/route');

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function testSkipsSubRequests(): void
    {
        $event = $this->createRequestEvent('POST', '/api/v1/clients', false);

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function testSkipsUnauthenticatedRequests(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);

        $event = $this->createRequestEvent('POST', '/api/v1/clients');

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function testSkipsUserWithoutShop(): void
    {
        $user = new User();
        $user->setEmail('noshop@example.com');
        $user->setFirstName('No');
        $user->setLastName('Shop');

        $this->setupAuthentication($user);

        $event = $this->createRequestEvent('POST', '/api/v1/clients');

        $this->sut->__invoke($event);

        self::assertNull($event->getResponse());
    }
}
