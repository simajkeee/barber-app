<?php

declare(strict_types=1);

namespace App\Reminder\MessageHandler;

use App\Reminder\Message\SendReminderEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendReminderEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $frontendUrl,
        private string $fromAddress,
    ) {
    }

    public function __invoke(SendReminderEmailMessage $message): void
    {
        $optOutUrl = \sprintf(
            '%s/%s/opt-out?token=%s',
            rtrim($this->frontendUrl, '/'),
            $message->locale,
            $message->optOutToken,
        );

        $templateFile = \sprintf('emails/reminder.%s.html.twig', $message->locale);

        $email = (new TemplatedEmail())
            ->from($this->fromAddress)
            ->to($message->clientEmail)
            ->subject($this->buildSubject($message->locale))
            ->htmlTemplate($templateFile)
            ->context([
                'clientFirstName' => $message->clientFirstName,
                'shopName' => $message->shopName,
                'shopPhone' => $message->shopPhone,
                'messageText' => $message->messageText,
                'optOutUrl' => $optOutUrl,
                'locale' => $message->locale,
            ]);

        $this->mailer->send($email);
    }

    private function buildSubject(string $locale): string
    {
        return match ($locale) {
            'vi' => 'Nhắc lịch hẹn',
            default => 'Appointment Reminder',
        };
    }
}
