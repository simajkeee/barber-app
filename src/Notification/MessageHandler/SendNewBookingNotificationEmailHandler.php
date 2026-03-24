<?php

declare(strict_types=1);

namespace App\Notification\MessageHandler;

use App\Notification\Message\SendNewBookingNotificationEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendNewBookingNotificationEmailHandler
{
    private const TZ_NAME = 'Asia/Ho_Chi_Minh';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
    ) {
    }

    public function __invoke(SendNewBookingNotificationEmailMessage $message): void
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $startTimeLocal = $message->startTimeUtc->setTimezone($tz);

        $email = (new TemplatedEmail())
            ->from($this->fromAddress)
            ->to($message->ownerEmail)
            ->subject($this->buildSubject($message->locale, $message->clientFullName, $message->serviceName))
            ->htmlTemplate('emails/new_booking_notification.'.$message->locale.'.html.twig')
            ->context([
                'clientFullName' => $message->clientFullName,
                'clientPhone' => $message->clientPhone,
                'serviceName' => $message->serviceName,
                'durationMinutes' => $message->durationMinutes,
                'startTime' => $startTimeLocal,
                'price' => $message->price,
                'notes' => $message->notes,
            ]);

        $this->mailer->send($email);
    }

    private function buildSubject(string $locale, string $clientFullName, string $serviceName): string
    {
        return match ($locale) {
            'en' => 'New Appointment: '.$clientFullName.' - '.$serviceName,
            default => 'Lịch hẹn mới: '.$clientFullName.' - '.$serviceName,
        };
    }
}
