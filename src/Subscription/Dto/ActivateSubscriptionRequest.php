<?php

declare(strict_types=1);

namespace App\Subscription\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ActivateSubscriptionRequest
{
    public function __construct(
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(365)]
        public int $durationDays = 30,
    ) {
    }
}
