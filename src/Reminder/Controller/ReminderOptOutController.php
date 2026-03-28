<?php

declare(strict_types=1);

namespace App\Reminder\Controller;

use App\Common\Exception\ApiException;
use App\Reminder\Dto\OptOutRequest;
use App\Reminder\Repository\ReminderOptOutTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class ReminderOptOutController
{
    public function __construct(
        private ReminderOptOutTokenRepository $optOutTokenRepository,
        private EntityManagerInterface $em,
        private RateLimiterFactory $publicBookingLimiter,
    ) {
    }

    #[Route('/api/v1/public/reminders/opt-out', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] OptOutRequest $dto,
        Request $request,
    ): JsonResponse {
        $limiter = $this->publicBookingLimiter->create($request->getClientIp() ?? 'unknown');
        $limiter->consume()->ensureAccepted();

        $token = $this->optOutTokenRepository->findByToken($dto->token);
        if (null === $token) {
            throw new ApiException('INVALID_OPT_OUT_TOKEN', 'Invalid or unknown token.', 404);
        }

        $client = $token->getClient();
        $client->setReminderOptOut(true);
        $this->em->flush();

        return new JsonResponse(['message' => 'You have been unsubscribed from reminders.']);
    }
}
