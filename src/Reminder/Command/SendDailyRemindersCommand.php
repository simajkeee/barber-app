<?php

declare(strict_types=1);

namespace App\Reminder\Command;

use App\Entity\Client;
use App\Reminder\Entity\ReminderOptOutToken;
use App\Reminder\Message\SendReminderEmailMessage;
use App\Reminder\Repository\ReminderOptOutTokenRepository;
use App\Reminder\Service\ReminderService;
use App\Reminder\Service\ReminderSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:send-daily-reminders',
    description: 'Send automated reminder emails to clients who are due for a visit',
)]
final class SendDailyRemindersCommand extends Command
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly ReminderService $reminderService,
        private readonly ReminderSettingsService $reminderSettingsService,
        private readonly ReminderOptOutTokenRepository $optOutTokenRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('shop-id', null, InputOption::VALUE_REQUIRED, 'Restrict to a single shop UUID')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Log what would be sent without dispatching');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $shopIdFilter = $input->getOption('shop-id');

        if ($dryRun) {
            $io->note('Dry-run mode — no emails will be dispatched and no records updated.');
        }

        $shopId = null !== $shopIdFilter ? Uuid::fromString($shopIdFilter) : null;
        $shops = $this->reminderSettingsService->findShopsWithAutomatedEmail($shopId);

        if ([] === $shops) {
            $io->warning('No shops found with automated email enabled.');

            return Command::SUCCESS;
        }

        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $now = new \DateTimeImmutable('now', $tz);

        foreach ($shops as $shop) {
            $locale = $shop->getOwner()->getLocale()->value;
            $dispatched = 0;
            $cursor = null;

            do {
                $result = $this->reminderService->getEmailReminderCandidates($shop, $locale, self::BATCH_SIZE, $cursor);

                $clients = array_map(fn (array $entry) => $entry['client'], $result['candidates']);
                $tokenMap = $this->getOrCreateTokens($clients);

                foreach ($result['candidates'] as $entry) {
                    $client = $entry['client'];
                    $candidate = $entry['candidate'];
                    $token = $tokenMap[(string) $client->getId()];

                    if (!$dryRun) {
                        $this->messageBus->dispatch(new SendReminderEmailMessage(
                            clientId: (string) $client->getId(),
                            shopId: (string) $shop->getId(),
                            clientEmail: $client->getEmail(),
                            clientFirstName: $client->getFirstName(),
                            shopName: $shop->getName(),
                            shopPhone: $shop->getPhone(),
                            messageText: $candidate->message,
                            optOutToken: $token->getToken(),
                            locale: $locale,
                        ));

                        $client->setLastRemindedAt($now);
                    }

                    ++$dispatched;
                }

                if (!$dryRun && [] !== $result['candidates']) {
                    $this->em->flush();
                }

                $cursor = $result['cursor'];

                $this->em->clear();
            } while ($result['hasMore']);

            $this->logger->info('Dispatched {count} reminder emails for shop {shopId}', [
                'count' => $dispatched,
                'shopId' => (string) $shop->getId(),
            ]);

            $io->success(\sprintf(
                '%s %d reminder email(s) for shop "%s"',
                $dryRun ? 'Would dispatch' : 'Dispatched',
                $dispatched,
                $shop->getName(),
            ));
        }

        return Command::SUCCESS;
    }

    /**
     * @param Client[] $clients
     *
     * @return array<string, ReminderOptOutToken> keyed by client ID
     */
    private function getOrCreateTokens(array $clients): array
    {
        $tokenMap = $this->optOutTokenRepository->findByClients($clients);

        foreach ($clients as $client) {
            $clientId = (string) $client->getId();
            if (isset($tokenMap[$clientId])) {
                continue;
            }

            $token = new ReminderOptOutToken();
            $token->setClient($client);
            $this->em->persist($token);
            $tokenMap[$clientId] = $token;
        }

        return $tokenMap;
    }
}
