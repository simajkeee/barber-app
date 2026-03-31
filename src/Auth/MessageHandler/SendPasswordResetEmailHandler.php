<?php

declare(strict_types=1);

namespace App\Auth\MessageHandler;

use App\Auth\Message\SendPasswordResetEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

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
        $resetLink = sprintf(
            '%s/%s/reset-password?token=%s',
            rtrim($this->frontendUrl, '/'),
            $message->locale,
            $message->rawToken,
        );

        $locale = in_array($message->locale, ['vi', 'en'], true) ? $message->locale : 'vi';

        $subject = $locale === 'vi'
            ? 'Đặt lại mật khẩu BarberPro'
            : 'Reset your BarberPro password';

        $email = (new TemplatedEmail())
            ->to($message->email)
            ->subject($subject)
            ->htmlTemplate("emails/password_reset.{$locale}.html.twig")
            ->context([
                'firstName'        => $message->firstName,
                'resetUrl'         => $resetLink,
                'expiresInMinutes' => 60,
                'locale'           => $locale,
            ]);

        $this->mailer->send($email);
    }
}
