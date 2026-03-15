<?php

declare(strict_types=1);

namespace App\Appointment\Enum;

enum AppointmentStatus: string
{
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function isTerminal(): bool
    {
        return $this !== self::SCHEDULED;
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this !== self::SCHEDULED) {
            return false;
        }

        return in_array($target, [self::COMPLETED, self::CANCELLED, self::NO_SHOW], true);
    }
}
