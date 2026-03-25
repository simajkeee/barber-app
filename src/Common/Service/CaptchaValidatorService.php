<?php

declare(strict_types=1);

namespace App\Common\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CaptchaValidatorService implements CaptchaValidatorInterface
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $turnstileSecretKey,
    ) {
    }

    public function validate(string $token): bool
    {
        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret' => $this->turnstileSecretKey,
                    'response' => $token,
                ],
            ]);

            $data = $response->toArray(false);

            return isset($data['success']) && true === $data['success'];
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Turnstile verification failed: network error', [
                'exception' => $e->getMessage(),
            ]);

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Turnstile verification failed: unexpected error', [
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
