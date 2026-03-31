<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\MessageHandler;

use App\Subscription\Message\SendTrialEndingEmailMessage;
use App\Subscription\MessageHandler\SendTrialEndingEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(SendTrialEndingEmailHandler::class)]
final class SendTrialEndingEmailHandlerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private SendTrialEndingEmailHandler $handler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->handler = new SendTrialEndingEmailHandler($this->mailer, 'noreply@barberpro.com');
    }

    #[Test]
    public function testFormatsDateInVietnamese(): void
    {
        $trialEndsAt = new \DateTimeImmutable('2026-04-15 10:00:00', new \DateTimeZone('UTC'));

        $message = new SendTrialEndingEmailMessage(
            ownerEmail: 'owner@example.com',
            ownerFirstName: 'Nguyen',
            shopName: 'Test Barber',
            trialEndsAtUtc: $trialEndsAt,
            locale: 'vi',
            upgradeUrl: 'https://example.com/dashboard/subscription',
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                $context = $email->getContext();
                // UTC 10:00 → ICT 17:00 same day, so date is 15/04/2026
                self::assertSame('15/04/2026', $context['trialEndsAt']);
                self::assertSame('Gói dùng thử của bạn sẽ kết thúc trong 3 ngày', $email->getSubject());
                self::assertSame('emails/trial_ending.vi.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testFormatsDateInEnglish(): void
    {
        $trialEndsAt = new \DateTimeImmutable('2026-04-15 10:00:00', new \DateTimeZone('UTC'));

        $message = new SendTrialEndingEmailMessage(
            ownerEmail: 'owner@example.com',
            ownerFirstName: 'John',
            shopName: 'Test Barber',
            trialEndsAtUtc: $trialEndsAt,
            locale: 'en',
            upgradeUrl: 'https://example.com/dashboard/subscription',
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                $context = $email->getContext();
                // UTC 10:00 → ICT 17:00 same day
                self::assertSame('April 15, 2026', $context['trialEndsAt']);
                self::assertSame('Your free trial ends in 3 days', $email->getSubject());
                self::assertSame('emails/trial_ending.en.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testSendsToCorrectEmail(): void
    {
        $message = new SendTrialEndingEmailMessage(
            ownerEmail: 'specific@test.com',
            ownerFirstName: 'Test',
            shopName: 'Shop',
            trialEndsAtUtc: new \DateTimeImmutable('2026-04-15', new \DateTimeZone('UTC')),
            locale: 'vi',
            upgradeUrl: 'https://example.com',
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                self::assertSame(['specific@test.com'], array_map(fn ($a) => $a->getAddress(), $email->getTo()));

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testRendersCorrectTemplateContext(): void
    {
        $message = new SendTrialEndingEmailMessage(
            ownerEmail: 'owner@example.com',
            ownerFirstName: 'Nguyen',
            shopName: 'Fancy Barber',
            trialEndsAtUtc: new \DateTimeImmutable('2026-04-15 10:00:00', new \DateTimeZone('UTC')),
            locale: 'en',
            upgradeUrl: 'https://example.com/dashboard/subscription',
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                $context = $email->getContext();
                self::assertSame('Nguyen', $context['ownerFirstName']);
                self::assertSame('Fancy Barber', $context['shopName']);
                self::assertSame('https://example.com/dashboard/subscription', $context['upgradeUrl']);
                self::assertArrayHasKey('trialEndsAt', $context);

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testDefaultsToViForUnknownLocale(): void
    {
        $message = new SendTrialEndingEmailMessage(
            ownerEmail: 'owner@example.com',
            ownerFirstName: 'Test',
            shopName: 'Shop',
            trialEndsAtUtc: new \DateTimeImmutable('2026-04-15', new \DateTimeZone('UTC')),
            locale: 'ja',
            upgradeUrl: 'https://example.com',
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                self::assertSame('emails/trial_ending.vi.html.twig', $email->getHtmlTemplate());
                self::assertSame('Gói dùng thử của bạn sẽ kết thúc trong 3 ngày', $email->getSubject());

                return true;
            }));

        ($this->handler)($message);
    }
}
