<?php

declare(strict_types=1);

namespace App\Notification\MessageHandler;

use App\Notification\Message\SendAppointmentCancelledEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendAppointmentCancelledEmailHandler
{
    private const TZ_NAME = 'Asia/Ho_Chi_Minh';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
    ) {
    }

    public function __invoke(SendAppointmentCancelledEmailMessage $message): void
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $startTimeLocal = $message->startTimeUtc->setTimezone($tz);

        $email = (new TemplatedEmail())
            ->from($this->fromAddress)
            ->to($message->clientEmail)
            ->subject($this->buildSubject($message->locale, $message->shopName))
            ->htmlTemplate('emails/appointment_cancelled.'.$message->locale.'.html.twig')
            ->context([
                'clientFirstName' => $message->clientFirstName,
                'serviceName' => $message->serviceName,
                'startTime' => $startTimeLocal,
                'shopName' => $message->shopName,
                'shopPhone' => $message->shopPhone,
            ]);

        $this->mailer->send($email);
    }

    private function buildSubject(string $locale, string $shopName): string
    {
        return match ($locale) {
            'en' => 'Appointment Cancelled - '.$shopName,
            default => 'Lịch hẹn đã bị hủy - '.$shopName,
        };
    }
}
