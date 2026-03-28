<?php

declare(strict_types=1);

namespace App\Subscription\MessageHandler;

use App\Repository\ShopRepository;
use App\Subscription\Message\SendRenewalReminderEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class SendRenewalReminderEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private ShopRepository $shopRepository,
        private string $fromAddress,
        private string $frontendUrl,
    ) {
    }

    public function __invoke(SendRenewalReminderEmailMessage $message): void
    {
        $shop = $this->shopRepository->find(Uuid::fromString($message->shopId));
        if (null === $shop) {
            return;
        }

        $owner = $shop->getOwner();
        $locale = $owner->getLocale()->value;
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');

        $email = (new TemplatedEmail())
            ->from($this->fromAddress)
            ->to($owner->getEmail())
            ->subject($this->buildSubject($locale))
            ->htmlTemplate(\sprintf('emails/renewal_reminder.%s.html.twig', $locale))
            ->context([
                'shopName' => $shop->getName(),
                'ownerFirstName' => $owner->getFirstName(),
                'endDate' => $message->endDate->setTimezone($tz)->format('d/m/Y'),
                'renewUrl' => rtrim($this->frontendUrl, '/').'/dashboard/subscription',
            ]);

        $this->mailer->send($email);
    }

    private function buildSubject(string $locale): string
    {
        return match ($locale) {
            'vi' => 'Gói PRO của bạn sắp hết hạn - BarberPro',
            default => 'Your PRO subscription expires soon - BarberPro',
        };
    }
}
