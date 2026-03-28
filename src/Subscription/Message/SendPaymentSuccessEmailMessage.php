<?php

declare(strict_types=1);

namespace App\Subscription\Message;

use App\Message\AsyncMessageInterface;

final readonly class SendPaymentSuccessEmailMessage implements AsyncMessageInterface
{
    public function __construct(
        public string $shopId,
        public string $transId,
        public int $amountVnd,
        public \DateTimeImmutable $endDate,
    ) {
    }
}
