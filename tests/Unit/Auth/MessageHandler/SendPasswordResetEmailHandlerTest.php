<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\MessageHandler;

use App\Auth\Message\SendPasswordResetEmailMessage;
use App\Auth\MessageHandler\SendPasswordResetEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(SendPasswordResetEmailHandler::class)]
final class SendPasswordResetEmailHandlerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private SendPasswordResetEmailHandler $sut;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->sut = new SendPasswordResetEmailHandler($this->mailer, 'https://barberpro.com');
    }

    #[Test]
    public function testSendsViEmailWithHtmlTemplate(): void
    {
        $message = new SendPasswordResetEmailMessage('user@example.com', 'abc123token', 'vi', 'Hùng');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('user@example.com', $email->getTo()[0]->getAddress());
                self::assertSame('Đặt lại mật khẩu BarberPro', $email->getSubject());
                self::assertSame('emails/password_reset.vi.html.twig', $email->getHtmlTemplate());

                $context = $email->getContext();
                self::assertSame('Hùng', $context['firstName']);
                self::assertSame('https://barberpro.com/vi/reset-password?token=abc123token', $context['resetUrl']);
                self::assertSame(60, $context['expiresInMinutes']);
                self::assertSame('vi', $context['locale']);

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testSendsEnEmailWithHtmlTemplate(): void
    {
        $message = new SendPasswordResetEmailMessage('user@example.com', 'token123', 'en', 'John');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('Reset your BarberPro password', $email->getSubject());
                self::assertSame('emails/password_reset.en.html.twig', $email->getHtmlTemplate());

                $context = $email->getContext();
                self::assertSame('John', $context['firstName']);
                self::assertSame('en', $context['locale']);

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testSubjectIsLocaleAware(): void
    {
        $viMessage = new SendPasswordResetEmailMessage('a@b.com', 't', 'vi', 'A');
        $enMessage = new SendPasswordResetEmailMessage('a@b.com', 't', 'en', 'A');

        $subjects = [];
        $this->mailer->method('send')
            ->willReturnCallback(function (TemplatedEmail $email) use (&$subjects): void {
                $subjects[] = $email->getSubject();
            });

        ($this->sut)($viMessage);
        ($this->sut)($enMessage);

        self::assertSame('Đặt lại mật khẩu BarberPro', $subjects[0]);
        self::assertSame('Reset your BarberPro password', $subjects[1]);
    }

    #[Test]
    public function testResetUrlContainsTokenAndLocalePrefix(): void
    {
        $message = new SendPasswordResetEmailMessage('a@b.com', 'mytoken', 'en', 'A');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame(
                    'https://barberpro.com/en/reset-password?token=mytoken',
                    $email->getContext()['resetUrl'],
                );

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testDefaultsToViForUnknownLocale(): void
    {
        $message = new SendPasswordResetEmailMessage('a@b.com', 't', 'xx', 'A');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('Đặt lại mật khẩu BarberPro', $email->getSubject());
                self::assertSame('emails/password_reset.vi.html.twig', $email->getHtmlTemplate());
                self::assertSame('vi', $email->getContext()['locale']);

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testFrontendUrlTrailingSlashIsStripped(): void
    {
        $handler = new SendPasswordResetEmailHandler($this->mailer, 'https://barberpro.com/');
        $message = new SendPasswordResetEmailMessage('a@b.com', 'token', 'en', 'A');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                $resetUrl = $email->getContext()['resetUrl'];
                self::assertSame('https://barberpro.com/en/reset-password?token=token', $resetUrl);
                self::assertStringNotContainsString('barberpro.com//en', $resetUrl);

                return true;
            }));

        ($handler)($message);
    }
}
