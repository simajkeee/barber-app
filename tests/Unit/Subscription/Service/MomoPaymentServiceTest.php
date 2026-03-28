<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Service;

use App\Common\Exception\ApiException;
use App\Subscription\Service\MomoPaymentService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(MomoPaymentService::class)]
final class MomoPaymentServiceTest extends TestCase
{
    private const string PARTNER_CODE = 'MOMO_TEST';
    private const string ACCESS_KEY = 'test_access_key';
    private const string SECRET_KEY = 'test_secret_key';
    private const string IPN_URL = 'https://api.test.com/webhooks/momo/ipn';
    private const string REDIRECT_URL = 'https://app.test.com/dashboard/subscription?payment=success';

    private HttpClientInterface&MockObject $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    private function createService(string $env = 'sandbox'): MomoPaymentService
    {
        return new MomoPaymentService(
            $this->httpClient,
            self::PARTNER_CODE,
            self::ACCESS_KEY,
            self::SECRET_KEY,
            $env,
            self::IPN_URL,
            self::REDIRECT_URL,
        );
    }

    #[Test]
    public function testCreatePaymentReturnsPayUrl(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'resultCode' => 0,
            'payUrl' => 'https://payment.momo.vn/v2/gateway/pay?t=abc123',
        ]);

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with('POST', 'https://test-payment.momo.vn/v2/gateway/api/create', self::callback(function (array $options): bool {
                $body = $options['json'];
                self::assertSame(self::PARTNER_CODE, $body['partnerCode']);
                self::assertSame('paymentCode', $body['requestType']);
                self::assertSame(self::IPN_URL, $body['ipnUrl']);
                self::assertSame(self::REDIRECT_URL, $body['redirectUrl']);
                self::assertSame('order-123', $body['orderId']);
                self::assertSame(299000, $body['amount']);
                self::assertSame('vi', $body['lang']);
                self::assertSame('BarberPro PRO - 1 tháng', $body['orderInfo']);
                self::assertArrayHasKey('requestId', $body);
                self::assertArrayHasKey('extraData', $body);
                self::assertArrayHasKey('signature', $body);

                $extraData = json_decode(base64_decode($body['extraData']), true);
                self::assertSame('shop-uuid-123', $extraData['shopId']);

                return true;
            }))
            ->willReturn($response);

        $sut = $this->createService();

        $payUrl = $sut->createPayment('order-123', 299000, 'shop-uuid-123');

        self::assertSame('https://payment.momo.vn/v2/gateway/pay?t=abc123', $payUrl);
    }

    #[Test]
    public function testCreatePaymentThrowsOnNonZeroResultCode(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'resultCode' => 11,
            'message' => 'Access denied.',
        ]);

        $this->httpClient->method('request')->willReturn($response);

        $sut = $this->createService();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Access denied.');

        try {
            $sut->createPayment('order-123', 299000, 'shop-uuid');
        } catch (ApiException $e) {
            self::assertSame('MOMO_INIT_FAILED', $e->errorCode);
            self::assertSame(502, $e->statusCode);

            throw $e;
        }
    }

    #[Test]
    public function testCreatePaymentThrowsWithDefaultMessageWhenMomoMessageMissing(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'resultCode' => 99,
        ]);

        $this->httpClient->method('request')->willReturn($response);

        $sut = $this->createService();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('MoMo payment initialization failed.');

        $sut->createPayment('order-123', 299000, 'shop-uuid');
    }

    #[Test]
    public function testCreatePaymentUsesSandboxUrlByDefault(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['resultCode' => 0, 'payUrl' => 'https://example.com']);

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with('POST', 'https://test-payment.momo.vn/v2/gateway/api/create', self::anything())
            ->willReturn($response);

        $this->createService('sandbox')->createPayment('o1', 299000, 's1');
    }

    #[Test]
    public function testCreatePaymentUsesProductionUrl(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['resultCode' => 0, 'payUrl' => 'https://example.com']);

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with('POST', 'https://payment.momo.vn/v2/gateway/api/create', self::anything())
            ->willReturn($response);

        $this->createService('production')->createPayment('o1', 299000, 's1');
    }

    #[Test]
    public function testVerifyIpnReturnsTrueForValidSignature(): void
    {
        $sut = $this->createService();

        $payload = $this->buildValidIpnPayload();

        self::assertTrue($sut->verifyIpn($payload));
    }

    #[Test]
    public function testVerifyIpnReturnsFalseForTamperedSignature(): void
    {
        $sut = $this->createService();

        $payload = $this->buildValidIpnPayload();
        $payload['signature'] = 'tampered_signature_value';

        self::assertFalse($sut->verifyIpn($payload));
    }

    #[Test]
    public function testVerifyIpnReturnsFalseForTamperedAmount(): void
    {
        $sut = $this->createService();

        $payload = $this->buildValidIpnPayload();
        $payload['amount'] = 1000;

        self::assertFalse($sut->verifyIpn($payload));
    }

    #[Test]
    public function testVerifyIpnReturnsFalseForMissingSignature(): void
    {
        $sut = $this->createService();

        $payload = $this->buildValidIpnPayload();
        unset($payload['signature']);

        self::assertFalse($sut->verifyIpn($payload));
    }

    #[Test]
    public function testVerifyIpnReturnsFalseForEmptyPayload(): void
    {
        $sut = $this->createService();

        self::assertFalse($sut->verifyIpn([]));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildValidIpnPayload(): array
    {
        $payload = [
            'amount' => 299000,
            'extraData' => base64_encode(json_encode(['shopId' => 'abc'])),
            'message' => 'Thành công.',
            'orderId' => 'barberpro-abc-123',
            'orderInfo' => 'BarberPro PRO - 1 tháng',
            'orderType' => 'momo_wallet',
            'partnerCode' => self::PARTNER_CODE,
            'payType' => 'qr',
            'requestId' => 'req-1',
            'responseTime' => 1624600146612,
            'resultCode' => 0,
            'transId' => 3568107123456,
        ];

        $rawSignature = \sprintf(
            'accessKey=%s&amount=%d&extraData=%s&message=%s&orderId=%s&orderInfo=%s&orderType=%s&partnerCode=%s&payType=%s&requestId=%s&responseTime=%d&resultCode=%d&transId=%d',
            self::ACCESS_KEY,
            $payload['amount'],
            $payload['extraData'],
            $payload['message'],
            $payload['orderId'],
            $payload['orderInfo'],
            $payload['orderType'],
            $payload['partnerCode'],
            $payload['payType'],
            $payload['requestId'],
            $payload['responseTime'],
            $payload['resultCode'],
            $payload['transId'],
        );
        $payload['signature'] = hash_hmac('sha256', $rawSignature, self::SECRET_KEY);

        return $payload;
    }
}
