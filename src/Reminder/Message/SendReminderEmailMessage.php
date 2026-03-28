<?php

declare(strict_types=1);

namespace App\Reminder\Message;

use App\Message\AsyncMessageInterface;

final readonly class SendReminderEmailMessage implements AsyncMessageInterface
{
    public function __construct(
        public string $clientId,
        public string $shopId,
        public string $clientEmail,
        public string $clientFirstName,
        public string $shopName,
        public string $shopPhone,
        public string $messageText,
        public string $optOutToken,
        public string $locale,
    ) {
    }
}
