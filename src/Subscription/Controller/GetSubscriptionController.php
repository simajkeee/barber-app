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

    #[Route('', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $shop = $this->shopManager->getShopForUser($user);
        if ($shop === null) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $subscription = $this->subscriptionService->getByShop($shop);
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');

        $data = [
            'id' => (string) $subscription->getId(),
            'plan' => $subscription->getPlan()->value,
            'status' => $subscription->getStatus()->value,
            'startDate' => $subscription->getStartDate()->setTimezone($tz)->format(\DateTimeInterface::ATOM),
            'endDate' => $subscription->getEndDate()?->setTimezone($tz)->format(\DateTimeInterface::ATOM),
            'usage' => [
                'appointmentsThisMonth' => $subscription->getMonthlyAppointmentCount(),
                'appointmentLimit' => $subscription->getPlan() === SubscriptionPlan::PRO ? null : SubscriptionService::FREE_APPOINTMENT_LIMIT,
                'limitReached' => $subscription->getPlan() === SubscriptionPlan::FREE && $subscription->getMonthlyAppointmentCount() >= SubscriptionService::FREE_APPOINTMENT_LIMIT,
            ],
        ];

        if ($subscription->getPlan() === SubscriptionPlan::PRO && $subscription->getEndDate() !== null) {
            $now = new \DateTimeImmutable('now', $tz);
            $data['daysRemaining'] = max(0, (int) $now->diff($subscription->getEndDate())->days);
        }

        return new JsonResponse($data);
    }
}
