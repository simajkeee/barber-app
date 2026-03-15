<?php

declare(strict_types=1);

namespace App\Subscription\Enum;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}
