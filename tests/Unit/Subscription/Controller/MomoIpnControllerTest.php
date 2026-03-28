<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Controller;

use App\Entity\Shop;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\ShopRepository;
use App\Subscription\Controller\MomoIpnController;
use App\Subscription\Message\SendPaymentSuccessEmailMessage;
use App\Subscription\Service\MomoPaymentService;
use App\Subscription\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(MomoIpnController::class)]
final class MomoIpnControllerTest extends TestCase
{
    private MomoPaymentService&MockObject $momoPaymentService;
    private SubscriptionService&MockObject $subscriptionService;
    private ShopRepository&MockObject $shopRepository;
    private EntityManagerInterface&MockObject $em;
    private MessageBusInterface&MockObject $messageBus;
    private LoggerInterface&MockObject $logger;
    private MomoIpnController $sut;

    protected function setUp(): void
    {
        $this->momoPaymentService = $this->createMock(MomoPaymentService::class);
        $this->subscriptionService = $this->createMock(SubscriptionService::class);
        $this->shopRepository = $this->createMock(ShopRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sut = new MomoIpnController(
            $this->momoPaymentService,
            $this->subscriptionService,
            $this->shopRepository,
            $this->em,
            $this->messageBus,
            $this->logger,
        );
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setFirstName('Test');
        $user->setLastName('Owner');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    private function createSubscription(Shop $shop, ?string $momoTransId = null): Subscription
    {
        $subscription = new Subscription();
        $subscription->setShop($shop);
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setCountResetAt(new \DateTimeImmutable());
        $subscription->setMomoTransId($momoTransId);

        return $subscription;
    }

    private function buildPayload(string $shopId, int $resultCode = 0, int $transId = 12345): string
    {
        $payload = [
            'partnerCode' => 'MOMO',
            'orderId' => 'barberpro-123',
            'requestId' => 'req-1',
            'amount' => 299000,
            'orderInfo' => 'BarberPro PRO',
            'orderType' => 'momo_wallet',
            'transId' => $transId,
            'resultCode' => $resultCode,
            'message' => 'Success',
            'payType' => 'qr',
            'responseTime' => 1624600146612,
            'extraData' => base64_encode(json_encode(['shopId' => $shopId])),
            'signature' => 'valid-sig',
        ];

        return json_encode($payload);
    }

    #[Test]
    public function testReturns204OnMalformedJson(): void
    {
        $this->em->expects(self::never())->method('flush');

        $request = new Request(content: 'not-valid-json{{{');

        $response = ($this->sut)($request);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    #[Test]
    public function testReturns204OnInvalidHmacSignature(): void
    {
        $shop = $this->createShop();
        $shopId = (string) $shop->getId();

        $this->momoPaymentService->method('verifyIpn')->willReturn(false);
        $this->subscriptionService->expects(self::never())->method('activateFromPayment');

        $request = new Request(content: $this->buildPayload($shopId));

        $response = ($this->sut)($request);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    #[Test]
    public function testReturns204OnFailedPayment(): void
    {
        $shop = $this->createShop();
        $shopId = (string) $shop->getId();

        $this->momoPaymentService->method('verifyIpn')->willReturn(true);
        $this->subscriptionService->expects(self::never())->method('activateFromPayment');
        $this->logger->expects(self::once())->method('info');

        $request = new Request(content: $this->buildPayload($shopId, resultCode: 1001));

        $response = ($this->sut)($request);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    #[Test]
    public function testReturns204WhenShopNotFound(): void
    {
        $this->momoPaymentService->method('verifyIpn')->willReturn(true);
        $this->shopRepository->method('find')->willReturn(null);
        $this->subscriptionService->expects(self::never())->method('activateFromPayment');

        $request = new Request(content: $this->buildPayload('00000000-0000-0000-0000-000000000001'));

        $response = ($this->sut)($request);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    #[Test]
    public function testReturns204WhenInvalidShopIdInExtraData(): void
    {
        $this->momoPaymentService->method('verifyIpn')->willReturn(true);
        $this->subscriptionService->expects(self::never())->method('activateFromPayment');

        $payload = json_encode([
            'resultCode' => 0,
            'extraData' => base64_encode(json_encode(['shopId' => 'not-a-uuid'])),
            'signature' => 'sig',
        ]);

        $request = new Request(content: $payload);

        $response = ($this->sut)($request);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    #[Test]
    public function testReturns204OnIdempotentReplay(): void
    {
        $shop = $this->createShop();
        $shopId = (string) $shop->getId();
        $subscription = $this->createSubscription($shop, '12345');

        $this->momoPaymentService->method('verifyIpn')->willReturn(true);
        $this->shopRepository->method('find')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);
        $this->subscriptionService->expects(self::never())->method('activateFromPayment');
        $this->em->expects(self::never())->method('flush');

        $request = new Request(content: $this->buildPayload($shopId, transId: 12345));

        $response = ($this->sut)($request);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    #[Test]
    public function testActivatesSubscriptionOnValidIpn(): void
    {
        $shop = $this->createShop();
        $shopId = (string) $shop->getId();
        $subscription = $this->createSubscription($shop);
        $subscription->setEndDate(new \DateTimeImmutable('+30 days'));

        $this->momoPaymentService->method('verifyIpn')->willReturn(true);
        $this->shopRepository->method('find')->willReturn($shop);
        $this->subscriptionService->method('getByShop')->willReturn($subscription);
        $this->subscriptionService->expects(self::once())
            ->method('activateFromPayment')
            ->with($subscription, '12345');
        $this->em->expects(self::once())->method('flush');

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (SendPaymentSuccessEmailMessage $msg) use ($shopId): bool {
                self::assertSame($shopId, $msg->shopId);
                self::assertSame('12345', $msg->transId);
                self::assertSame(299000, $msg->amountVnd);

                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $request = new Request(content: $this->buildPayload($shopId, transId: 12345));

        $response = ($this->sut)($request);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
