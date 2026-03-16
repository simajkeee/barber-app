<?php

declare(strict_types=1);

namespace App\Subscription\Command;

use App\Subscription\Service\SubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:subscriptions:reset-counters',
    description: 'Reset monthly appointment counters for all subscriptions',
)]
final class ResetMonthlyCountersCommand extends Command
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->subscriptionService->resetMonthlyCounters();

        $io->success(sprintf('Reset counters for %d subscription(s).', $count));

        return Command::SUCCESS;
    }
}
