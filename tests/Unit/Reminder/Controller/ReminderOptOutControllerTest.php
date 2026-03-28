<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reminder\Controller;

use App\Common\Exception\ApiException;
use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\User;
use App\Reminder\Controller\ReminderOptOutController;
use App\Reminder\Dto\OptOutRequest;
use App\Reminder\Entity\ReminderOptOutToken;
use App\Reminder\Repository\ReminderOptOutTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[CoversClass(ReminderOptOutController::class)]
final class ReminderOptOutControllerTest extends TestCase
{
    private ReminderOptOutTokenRepository&MockObject $tokenRepository;
    private EntityManagerInterface&MockObject $em;
    private RateLimiterFactory&MockObject $limiterFactory;
    private ReminderOptOutController $controller;

    protected function setUp(): void
    {
        $this->tokenRepository = $this->createMock(ReminderOptOutTokenRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->limiterFactory = $this->createMock(RateLimiterFactory::class);

        $limiter = $this->createMock(LimiterInterface::class);
        $rateLimit = $this->createMock(RateLimit::class);
        $rateLimit->method('isAccepted')->willReturn(true);
        $limiter->method('consume')->willReturn($rateLimit);
        $this->limiterFactory->method('create')->willReturn($limiter);

        $this->controller = new ReminderOptOutController(
            $this->tokenRepository,
            $this->em,
            $this->limiterFactory,
        );
    }

    private function createClient(): Client
    {
        $user = new User();
        $user->setEmail('barber@example.com');
        $user->setFirstName('B');
        $user->setLastName('B');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Shop');
        $shop->setAddress('Addr');
        $shop->setPhone('0901234567');
        $shop->setSlug('shop');

        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName('John');
        $client->setLastName('Doe');
        $client->setPhone('+84901234567');

        return $client;
    }

    #[Test]
    public function testValidTokenOptsOutClient(): void
    {
        $client = $this->createClient();
        self::assertFalse($client->isReminderOptOut());

        $token = new ReminderOptOutToken();
        $token->setClient($client);

        $this->tokenRepository->method('findByToken')->willReturn($token);
        $this->em->expects(self::once())->method('flush');

        $dto = new OptOutRequest(token: $token->getToken());
        $response = ($this->controller)($dto, new Request());

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($client->isReminderOptOut());

        $data = json_decode($response->getContent(), true);
        self::assertSame('You have been unsubscribed from reminders.', $data['message']);
    }

    #[Test]
    public function testInvalidTokenThrows404(): void
    {
        $this->tokenRepository->method('findByToken')->willReturn(null);

        $dto = new OptOutRequest(token: str_repeat('ab', 32));

        try {
            ($this->controller)($dto, new Request());
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(404, $e->statusCode);
            self::assertSame('INVALID_OPT_OUT_TOKEN', $e->errorCode);
        }
    }

    #[Test]
    public function testIdempotentOptOut(): void
    {
        $client = $this->createClient();
        $client->setReminderOptOut(true);

        $token = new ReminderOptOutToken();
        $token->setClient($client);

        $this->tokenRepository->method('findByToken')->willReturn($token);
        $this->em->expects(self::once())->method('flush');

        $dto = new OptOutRequest(token: $token->getToken());
        $response = ($this->controller)($dto, new Request());

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($client->isReminderOptOut());
    }
}
