<?php

declare(strict_types=1);

namespace App\Subscription\Command;

use App\Repository\SubscriptionRepository;
use App\Subscription\Message\SendRenewalReminderEmailMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:send-renewal-reminders',
    description: 'Send renewal reminder emails for PRO subscriptions expiring in 7 days',
)]
final class SendRenewalRemindersCommand extends Command
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Log intended actions without dispatching or saving')
            ->addOption('shop-id', null, InputOption::VALUE_REQUIRED, 'Process only the given shop UUID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $shopIdFilter = $input->getOption('shop-id');

        if (null !== $shopIdFilter && !Uuid::isValid($shopIdFilter)) {
            $io->error('Invalid shop UUID.');

            return Command::FAILURE;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $subscriptions = $this->subscriptionRepository->findExpiringInSevenDays($now);

        $count = 0;
        foreach ($subscriptions as $subscription) {
            $shopId = (string) $subscription->getShop()->getId();

            if (null !== $shopIdFilter && $shopId !== $shopIdFilter) {
                continue;
            }

            if ($dryRun) {
                $io->note(\sprintf(
                    'Would send reminder to shop %s (expires %s)',
                    $shopId,
                    $subscription->getEndDate()?->format('Y-m-d'),
                ));
                ++$count;

                continue;
            }

            $this->messageBus->dispatch(new SendRenewalReminderEmailMessage(
                shopId: $shopId,
                endDate: $subscription->getEndDate(),
            ));

            $subscription->setRenewalReminderSentAt($now);
            ++$count;
        }

        if ($count > 0 && !$dryRun) {
            $this->em->flush();
        }

        $io->success(\sprintf('%s %d renewal reminder(s).', $dryRun ? 'Would send' : 'Sent', $count));

        return Command::SUCCESS;
    }
}
