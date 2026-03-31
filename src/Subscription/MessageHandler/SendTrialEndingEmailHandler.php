<?php

declare(strict_types=1);

namespace App\Subscription\MessageHandler;

use App\Subscription\Message\SendTrialEndingEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendTrialEndingEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromAddress,
    ) {
    }

    public function __invoke(SendTrialEndingEmailMessage $message): void
    {
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $locale = \in_array($message->locale, ['vi', 'en'], true) ? $message->locale : 'vi';

        $trialEndsAt = $message->trialEndsAtUtc->setTimezone($tz);
        $dateFormatted = 'vi' === $locale
            ? $trialEndsAt->format('d/m/Y')
            : $trialEndsAt->format('F j, Y');

        $template = \sprintf('emails/trial_ending.%s.html.twig', $locale);

        $subject = 'vi' === $locale
            ? 'Gói dùng thử của bạn sẽ kết thúc trong 3 ngày'
            : 'Your free trial ends in 3 days';

        $email = (new TemplatedEmail())
            ->from($this->fromAddress)
            ->to($message->ownerEmail)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'ownerFirstName' => $message->ownerFirstName,
                'shopName' => $message->shopName,
                'trialEndsAt' => $dateFormatted,
                'upgradeUrl' => $message->upgradeUrl,
            ]);

        $this->mailer->send($email);
    }
}
