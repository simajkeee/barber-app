<?php

declare(strict_types=1);

namespace App\Subscription\Command;

use App\Repository\ShopRepository;
use App\Subscription\Service\SubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:subscriptions:backfill',
    description: 'Create FREE subscriptions for shops that have none',
)]
final class BackfillMissingSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly ShopRepository $shopRepository,
        private readonly SubscriptionService $subscriptionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = 0;
        foreach ($this->shopRepository->findAll() as $shop) {
            $this->subscriptionService->getByShop($shop);
            ++$count;
        }

        $io->success(sprintf('Processed %d shop(s) — missing FREE subscriptions created.', $count));

        return Command::SUCCESS;
    }
}
