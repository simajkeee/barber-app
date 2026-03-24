<?php

declare(strict_types=1);

namespace App\Notification\MessageHandler;

use App\Notification\Message\SendBookingConfirmationEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendBookingConfirmationEmailHandler
{
    private const TZ_NAME = 'Asia/Ho_Chi_Minh';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
    ) {
    }

    public function __invoke(SendBookingConfirmationEmailMessage $message): void
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $startTimeLocal = $message->startTimeUtc->setTimezone($tz);

        $email = (new TemplatedEmail())
            ->from($this->fromAddress)
            ->to($message->clientEmail)
            ->subject($this->buildSubject($message->locale, $message->shopName))
            ->htmlTemplate('emails/booking_confirmation.'.$message->locale.'.html.twig')
            ->context([
                'clientFirstName' => $message->clientFirstName,
                'serviceName' => $message->serviceName,
                'durationMinutes' => $message->durationMinutes,
                'startTime' => $startTimeLocal,
                'shopName' => $message->shopName,
                'shopAddress' => $message->shopAddress,
                'shopPhone' => $message->shopPhone,
            ]);

        $this->mailer->send($email);
    }

    private function buildSubject(string $locale, string $shopName): string
    {
        return match ($locale) {
            'en' => 'Booking Confirmation - '.$shopName,
            default => 'Xác nhận lịch hẹn - '.$shopName,
        };
    }
}
