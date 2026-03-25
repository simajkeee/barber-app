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
    name: 'app:subscriptions:expire-trials',
    description: 'Downgrade subscriptions whose free trial period has ended',
)]
final class ExpireTrialsCommand extends Command
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->subscriptionService->expireOverdueTrials();

        $io->success(\sprintf('Expired %d trial(s). Downgraded to FREE plan.', $count));

        return Command::SUCCESS;
    }
}
