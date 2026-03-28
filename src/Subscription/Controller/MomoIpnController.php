<?php

declare(strict_types=1);

namespace App\Subscription\Controller;

use App\Repository\ShopRepository;
use App\Subscription\Message\SendPaymentSuccessEmailMessage;
use App\Subscription\Service\MomoPaymentService;
use App\Subscription\Service\SubscriptionService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
final readonly class MomoIpnController
{
    public function __construct(
        private MomoPaymentService $momoPaymentService,
        private SubscriptionService $subscriptionService,
        private ShopRepository $shopRepository,
        private EntityManagerInterface $em,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/webhooks/momo/ipn', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        if (!$this->momoPaymentService->verifyIpn($payload)) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $resultCode = (int) ($payload['resultCode'] ?? -1);
        if (0 !== $resultCode) {
            $this->logger->info('MoMo payment failed', [
                'orderId' => $payload['orderId'] ?? '',
                'resultCode' => $resultCode,
            ]);

            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $extraData = json_decode(base64_decode((string) ($payload['extraData'] ?? '')), true);
        if (!\is_array($extraData)) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
        $shopId = $extraData['shopId'] ?? null;
        if (null === $shopId || !Uuid::isValid($shopId)) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $shop = $this->shopRepository->find(Uuid::fromString($shopId));
        if (null === $shop) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $subscription = $this->subscriptionService->getByShop($shop);
        $transId = (string) ($payload['transId'] ?? '');

        if ($subscription->getMomoTransId() === $transId) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $this->subscriptionService->activateFromPayment($subscription, $transId);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $this->messageBus->dispatch(new SendPaymentSuccessEmailMessage(
            shopId: $shopId,
            transId: $transId,
            amountVnd: (int) ($payload['amount'] ?? 0),
            endDate: $subscription->getEndDate(),
        ));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
