<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\Command;

use App\Subscription\Command\ResetMonthlyCountersCommand;
use App\Subscription\Service\SubscriptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ResetMonthlyCountersCommand::class)]
final class ResetMonthlyCountersCommandTest extends TestCase
{
    private SubscriptionService&MockObject $subscriptionService;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->subscriptionService = $this->createMock(SubscriptionService::class);
        $command = new ResetMonthlyCountersCommand($this->subscriptionService);
        $this->commandTester = new CommandTester($command);
    }

    #[Test]
    public function testCommandSucceedsAndReportsResetCount(): void
    {
        $this->subscriptionService->expects(self::once())
            ->method('resetMonthlyCounters')
            ->willReturn(12);

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('12', $this->commandTester->getDisplay());
    }

    #[Test]
    public function testCommandSucceedsWhenNothingToReset(): void
    {
        $this->subscriptionService->expects(self::once())
            ->method('resetMonthlyCounters')
            ->willReturn(0);

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('0', $this->commandTester->getDisplay());
    }
}
