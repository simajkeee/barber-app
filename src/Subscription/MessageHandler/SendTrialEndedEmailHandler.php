<?php

declare(strict_types=1);

namespace App\Subscription\MessageHandler;

use App\Subscription\Message\SendTrialEndedEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendTrialEndedEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromAddress,
    ) {
    }

    public function __invoke(SendTrialEndedEmailMessage $message): void
    {
        $locale = \in_array($message->locale, ['vi', 'en'], true) ? $message->locale : 'vi';
        $template = \sprintf('emails/trial_ended.%s.html.twig', $locale);

        $subject = 'vi' === $locale
            ? 'Gói dùng thử của bạn đã kết thúc'
            : 'Your free trial has ended';

        $email = (new TemplatedEmail())
            ->from($this->fromAddress)
            ->to($message->ownerEmail)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'ownerFirstName' => $message->ownerFirstName,
                'shopName' => $message->shopName,
                'upgradeUrl' => $message->upgradeUrl,
            ]);

        $this->mailer->send($email);
    }
}
