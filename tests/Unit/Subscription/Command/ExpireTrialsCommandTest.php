<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Command;

use App\Subscription\Command\ExpireTrialsCommand;
use App\Subscription\Service\SubscriptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ExpireTrialsCommand::class)]
final class ExpireTrialsCommandTest extends TestCase
{
    private SubscriptionService&MockObject $subscriptionService;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->subscriptionService = $this->createMock(SubscriptionService::class);
        $command = new ExpireTrialsCommand($this->subscriptionService);
        $this->commandTester = new CommandTester($command);
    }

    #[Test]
    public function testCommandSucceedsAndReportsExpiredCount(): void
    {
        $this->subscriptionService->expects(self::once())
            ->method('expireOverdueTrials')
            ->willReturn(3);

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('3', $this->commandTester->getDisplay());
    }

    #[Test]
    public function testCommandSucceedsWhenNothingToExpire(): void
    {
        $this->subscriptionService->expects(self::once())
            ->method('expireOverdueTrials')
            ->willReturn(0);

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('0', $this->commandTester->getDisplay());
    }
}
