<?php

declare(strict_types=1);

namespace App\Subscription\Message;

use App\Message\AsyncMessageInterface;

final readonly class SendRenewalReminderEmailMessage implements AsyncMessageInterface
{
    public function __construct(
        public string $shopId,
        public \DateTimeImmutable $endDate,
    ) {
    }
}
