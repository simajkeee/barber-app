<?php

declare(strict_types=1);

namespace App\Shop\Event;

use Symfony\Component\Uid\Uuid;

final readonly class ShopCreatedEvent
{
    public function __construct(
        public Uuid $shopId,
    ) {
    }
}
