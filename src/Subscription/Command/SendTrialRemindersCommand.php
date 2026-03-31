<?php

declare(strict_types=1);

namespace App\Subscription\Command;

use App\Subscription\Message\SendTrialEndingEmailMessage;
use App\Subscription\Service\SubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:subscriptions:send-trial-reminders',
    description: 'Send "trial ending soon" emails to barbers whose trial expires in ~3 days',
)]
final class SendTrialRemindersCommand extends Command
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly MessageBusInterface $bus,
        private readonly string $frontendUrl,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $expiring = $this->subscriptionService->sendTrialExpiryReminders();

        foreach ($expiring as $subscription) {
            $owner = $subscription->getShop()->getOwner();
            $this->bus->dispatch(new SendTrialEndingEmailMessage(
                ownerEmail: $owner->getEmail(),
                ownerFirstName: $owner->getFirstName(),
                shopName: $subscription->getShop()->getName(),
                trialEndsAtUtc: $subscription->getTrialEndsAt(),
                locale: $owner->getLocale()->value,
                upgradeUrl: rtrim($this->frontendUrl, '/').'/dashboard/subscription',
            ));
        }

        $io->success(\sprintf('Sent %d trial reminder(s).', \count($expiring)));

        return Command::SUCCESS;
    }
}
