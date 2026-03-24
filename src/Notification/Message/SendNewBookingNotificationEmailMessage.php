<?php

declare(strict_types=1);

namespace App\Notification\Message;

use App\Message\AsyncMessageInterface;

readonly class SendNewBookingNotificationEmailMessage implements AsyncMessageInterface
{
    public function __construct(
        public string $ownerEmail,
        public string $clientFullName,
        public string $clientPhone,
        public string $serviceName,
        public int $durationMinutes,
        public int $price,
        public \DateTimeImmutable $startTimeUtc,
        public ?string $notes,
        public string $locale,
    ) {
    }
}
