<?php

declare(strict_types=1);

namespace App\Auth\MessageHandler;

use App\Auth\Message\SendPasswordResetEmailMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final readonly class SendPasswordResetEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $frontendUrl,
    ) {
    }

    public function __invoke(SendPasswordResetEmailMessage $message): void
    {
        $resetLink = \sprintf(
            '%s/%s/reset-password?token=%s',
            rtrim($this->frontendUrl, '/'),
            $message->locale,
            $message->rawToken,
        );

        $email = (new Email())
            ->to($message->email)
            ->subject('Password Reset Request')
            ->text(\sprintf(
                "You requested a password reset.\n\nClick the link below to set a new password:\n%s\n\nThis link expires in 1 hour.\n\nIf you did not request this, please ignore this email.",
                $resetLink,
            ));

        $this->mailer->send($email);
    }
}
