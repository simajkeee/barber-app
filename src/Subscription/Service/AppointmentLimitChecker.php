<?php

declare(strict_types=1);

namespace App\Subscription\Service;

use App\Common\Exception\ApiException;
use App\Entity\Shop;

final class AppointmentLimitChecker
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function check(Shop $shop): void
    {
        if (!$this->subscriptionService->canCreateAppointment($shop)) {
            throw new ApiException(
                'APPOINTMENT_LIMIT_REACHED',
                'Monthly appointment limit reached. Upgrade to PRO for unlimited appointments.',
                403,
            );
        }
    }
}
