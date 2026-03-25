<?php

declare(strict_types=1);

namespace App\Common\EventListener;

use Sentry\State\HubInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

#[AsEventListener]
final readonly class MessengerFailureListener
{
    public function __construct(
        private HubInterface $sentryHub,
    ) {
    }

    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $this->sentryHub->captureException($event->getThrowable());
    }
}
