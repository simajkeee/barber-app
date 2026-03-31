<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\MessageHandler;

use App\Subscription\Message\SendTrialEndedEmailMessage;
use App\Subscription\MessageHandler\SendTrialEndedEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(SendTrialEndedEmailHandler::class)]
final class SendTrialEndedEmailHandlerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private SendTrialEndedEmailHandler $handler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->handler = new SendTrialEndedEmailHandler($this->mailer, 'noreply@barberpro.com');
    }

    private function createMessage(string $locale = 'vi'): SendTrialEndedEmailMessage
    {
        return new SendTrialEndedEmailMessage(
            ownerEmail: 'owner@example.com',
            ownerFirstName: 'Nguyen',
            shopName: 'Test Barber',
            locale: $locale,
            upgradeUrl: 'https://example.com/dashboard/subscription',
        );
    }

    #[Test]
    public function testSendsViEmailWithCorrectSubject(): void
    {
        $message = $this->createMessage('vi');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                self::assertSame(['owner@example.com'], array_map(fn ($a) => $a->getAddress(), $email->getTo()));
                self::assertSame('Gói dùng thử của bạn đã kết thúc', $email->getSubject());
                self::assertSame('emails/trial_ended.vi.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testSendsEnEmailWithCorrectSubject(): void
    {
        $message = $this->createMessage('en');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                self::assertSame('Your free trial has ended', $email->getSubject());
                self::assertSame('emails/trial_ended.en.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testRendersCorrectTemplateContext(): void
    {
        $message = $this->createMessage('en');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                $context = $email->getContext();
                self::assertSame('Nguyen', $context['ownerFirstName']);
                self::assertSame('Test Barber', $context['shopName']);
                self::assertSame('https://example.com/dashboard/subscription', $context['upgradeUrl']);

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testDefaultsToViForUnknownLocale(): void
    {
        $message = new SendTrialEndedEmailMessage(
            ownerEmail: 'owner@example.com',
            ownerFirstName: 'Test',
            shopName: 'Shop',
            locale: 'fr',
            upgradeUrl: 'https://example.com',
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                self::assertSame('emails/trial_ended.vi.html.twig', $email->getHtmlTemplate());
                self::assertSame('Gói dùng thử của bạn đã kết thúc', $email->getSubject());

                return true;
            }));

        ($this->handler)($message);
    }
}
