<?php

declare(strict_types=1);

namespace App\Subscription\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Shop\Service\ShopManager;
use App\Subscription\Enum\SubscriptionPlan;
use App\Subscription\Service\SubscriptionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class GetSubscriptionController
{
    public function __construct(
        private ShopManager $shopManager,
        private SubscriptionService $subscriptionService,
    ) {
    }

    #[Route('/subscription', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $subscription = $this->subscriptionService->getByShop($shop);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $now = new \DateTimeImmutable('now', $tz);

        $trialEndsAt = $subscription->getTrialEndsAt();
        $isInTrial = $subscription->isInTrial();

        $trialDaysRemaining = null;
        if ($isInTrial && null !== $trialEndsAt) {
            $diff = $now->diff($trialEndsAt);
            $trialDaysRemaining = 0 === $diff->invert ? (int) $diff->days : 0;
        }

        $data = [
            'id' => (string) $subscription->getId(),
            'plan' => $subscription->getPlan()->value,
            'status' => $subscription->getStatus()->value,
            'startDate' => $subscription->getStartDate()->setTimezone($tz)->format(\DateTimeInterface::ATOM),
            'endDate' => $subscription->getEndDate()?->setTimezone($tz)->format(\DateTimeInterface::ATOM),
            'trial' => [
                'isInTrial' => $isInTrial,
                'trialEndsAt' => $trialEndsAt?->setTimezone($tz)->format(\DateTimeInterface::ATOM),
                'trialDaysRemaining' => $trialDaysRemaining,
            ],
            'usage' => [
                'appointmentsThisMonth' => $subscription->getMonthlyAppointmentCount(),
                'appointmentLimit' => SubscriptionPlan::PRO === $subscription->getPlan() ? null : SubscriptionService::FREE_APPOINTMENT_LIMIT,
                'limitReached' => SubscriptionPlan::FREE === $subscription->getPlan()
                    && $subscription->getMonthlyAppointmentCount() >= SubscriptionService::FREE_APPOINTMENT_LIMIT,
            ],
        ];

        return new JsonResponse($data);
    }
}
