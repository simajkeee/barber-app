<?php

declare(strict_types=1);

namespace App\Notification\Message;

use App\Message\AsyncMessageInterface;

readonly class SendAppointmentCancelledEmailMessage implements AsyncMessageInterface
{
    public function __construct(
        public string $clientEmail,
        public string $clientFirstName,
        public string $serviceName,
        public \DateTimeImmutable $startTimeUtc,
        public string $shopName,
        public string $shopPhone,
        public string $locale,
    ) {
    }
}
