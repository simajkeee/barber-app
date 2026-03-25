<?php

declare(strict_types=1);

namespace App\Tests\Unit\Common\EventListener;

use App\Common\EventListener\MessengerFailureListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentry\State\HubInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

#[CoversClass(MessengerFailureListener::class)]
final class MessengerFailureListenerTest extends TestCase
{
    private HubInterface $sentryHub;
    private MessengerFailureListener $sut;

    protected function setUp(): void
    {
        $this->sentryHub = $this->createMock(HubInterface::class);
        $this->sut = new MessengerFailureListener($this->sentryHub);
    }

    #[Test]
    public function itReportsExceptionWhenRetryIsExhausted(): void
    {
        $exception = new \RuntimeException('Job failed permanently');
        $envelope = new Envelope(new \stdClass());
        $event = new WorkerMessageFailedEvent($envelope, 'async', $exception);

        $this->sentryHub
            ->expects($this->once())
            ->method('captureException')
            ->with($exception);

        ($this->sut)($event);
    }

    #[Test]
    public function itDoesNotReportWhenJobWillRetry(): void
    {
        $exception = new \RuntimeException('Transient failure');
        $envelope = new Envelope(new \stdClass());
        $event = new WorkerMessageFailedEvent($envelope, 'async', $exception);
        $event->setForRetry();

        $this->sentryHub
            ->expects($this->never())
            ->method('captureException');

        ($this->sut)($event);
    }
}
