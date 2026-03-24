<?php

declare(strict_types=1);

namespace App\Notification\Message;

use App\Message\AsyncMessageInterface;

readonly class SendBookingConfirmationEmailMessage implements AsyncMessageInterface
{
    public function __construct(
        public string $clientEmail,
        public string $clientFirstName,
        public string $serviceName,
        public int $durationMinutes,
        public \DateTimeImmutable $startTimeUtc,
        public string $shopName,
        public string $shopAddress,
        public string $shopPhone,
        public string $locale,
    ) {
    }
}
