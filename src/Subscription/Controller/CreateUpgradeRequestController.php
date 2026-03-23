<?php

declare(strict_types=1);

namespace App\Subscription\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Subscription\Dto\CreateUpgradeRequestDto;
use App\Subscription\Entity\UpgradeRequest;
use App\Subscription\Repository\UpgradeRequestRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class CreateUpgradeRequestController
{
    private const int DUPLICATE_CHECK_DAYS = 7;

    public function __construct(
        private UpgradeRequestRepository $upgradeRequestRepository,
        private RateLimiterFactory $upgradeRequestLimiter,
    ) {
    }

    #[Route('/upgrade-request', methods: ['POST'])]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapRequestPayload] CreateUpgradeRequestDto $dto,
    ): JsonResponse {
        $this->upgradeRequestLimiter->create((string) $user->getId())->consume()->ensureAccepted();

        $existing = $this->upgradeRequestRepository->findRecentByUser($user, self::DUPLICATE_CHECK_DAYS);
        if (null !== $existing) {
            throw new ApiException('UPGRADE_REQUEST_ALREADY_SUBMITTED', 'An upgrade request was already submitted within the last 7 days.', 409);
        }

        $upgradeRequest = new UpgradeRequest();
        $upgradeRequest->setUser($user);
        $upgradeRequest->setName($dto->name);
        $upgradeRequest->setEmail($dto->email);
        $upgradeRequest->setPhone($dto->phone);
        $upgradeRequest->setMessage($dto->message);

        $this->upgradeRequestRepository->save($upgradeRequest);

        return new JsonResponse([
            'id' => (string) $upgradeRequest->getId(),
            'createdAt' => $upgradeRequest->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], 201);
    }
}
