<?php

declare(strict_types=1);

namespace App\Subscription\Service;

use App\Common\Exception\ApiException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MomoPaymentService
{
    private const string SANDBOX_URL = 'https://test-payment.momo.vn/v2/gateway/api/create';
    private const string PRODUCTION_URL = 'https://payment.momo.vn/v2/gateway/api/create';
    private const string ORDER_INFO = 'BarberPro PRO - 1 tháng';
    private const string REQUEST_TYPE = 'captureWallet';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $partnerCode,
        private readonly string $accessKey,
        private readonly string $secretKey,
        private readonly string $env,
        private readonly string $ipnUrl,
        private readonly string $redirectUrl,
    ) {
    }

    public function createPayment(string $orderId, int $amountVnd, string $shopId): string
    {
        $requestId = Uuid::v4()->toRfc4122();
        $extraData = base64_encode(json_encode(['shopId' => $shopId], \JSON_THROW_ON_ERROR));

        $rawSignature = \sprintf(
            'accessKey=%s&amount=%d&extraData=%s&ipnUrl=%s&orderId=%s&orderInfo=%s&partnerCode=%s&redirectUrl=%s&requestId=%s&requestType=%s',
            $this->accessKey,
            $amountVnd,
            $extraData,
            $this->ipnUrl,
            $orderId,
            self::ORDER_INFO,
            $this->partnerCode,
            $this->redirectUrl,
            $requestId,
            self::REQUEST_TYPE,
        );
        $signature = hash_hmac('sha256', $rawSignature, $this->secretKey);

        $body = [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'requestType' => self::REQUEST_TYPE,
            'ipnUrl' => $this->ipnUrl,
            'redirectUrl' => $this->redirectUrl,
            'orderId' => $orderId,
            'amount' => $amountVnd,
            'lang' => 'vi',
            'orderInfo' => self::ORDER_INFO,
            'requestId' => $requestId,
            'extraData' => $extraData,
            'signature' => $signature,
        ];

        $response = $this->httpClient->request('POST', $this->getEndpointUrl(), [
            'json' => $body,
        ]);

        /** @var array{resultCode: int, payUrl?: string, message?: string} $data */
        $data = $response->toArray(false);
        if (0 !== $data['resultCode']) {
            throw new ApiException('MOMO_INIT_FAILED', $data['message'] ?? 'MoMo payment initialization failed.', 502);
        }

        return $data['payUrl'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function verifyIpn(array $payload): bool
    {
        $rawSignature = \sprintf(
            'accessKey=%s&amount=%d&extraData=%s&message=%s&orderId=%s&orderInfo=%s&orderType=%s&partnerCode=%s&payType=%s&requestId=%s&responseTime=%d&resultCode=%d&transId=%d',
            $this->accessKey,
            (int) ($payload['amount'] ?? 0),
            (string) ($payload['extraData'] ?? ''),
            (string) ($payload['message'] ?? ''),
            (string) ($payload['orderId'] ?? ''),
            (string) ($payload['orderInfo'] ?? ''),
            (string) ($payload['orderType'] ?? ''),
            (string) ($payload['partnerCode'] ?? ''),
            (string) ($payload['payType'] ?? ''),
            (string) ($payload['requestId'] ?? ''),
            (int) ($payload['responseTime'] ?? 0),
            (int) ($payload['resultCode'] ?? -1),
            (int) ($payload['transId'] ?? 0),
        );

        $expected = hash_hmac('sha256', $rawSignature, $this->secretKey);

        return hash_equals($expected, (string) ($payload['signature'] ?? ''));
    }

    private function getEndpointUrl(): string
    {
        return 'production' === $this->env ? self::PRODUCTION_URL : self::SANDBOX_URL;
    }
}
