<?php

declare(strict_types=1);

namespace App\Subscription\Enum;

enum SubscriptionPlan: string
{
    case FREE = 'free';
    case PRO = 'pro';
}
