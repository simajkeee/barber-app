<?php

declare(strict_types=1);

namespace App\Subscription\Message;

use App\Message\AsyncMessageInterface;

final readonly class SendTrialEndingEmailMessage implements AsyncMessageInterface
{
    public function __construct(
        public string $ownerEmail,
        public string $ownerFirstName,
        public string $shopName,
        public \DateTimeImmutable $trialEndsAtUtc,
        public string $locale,
        public string $upgradeUrl,
    ) {
    }
}
