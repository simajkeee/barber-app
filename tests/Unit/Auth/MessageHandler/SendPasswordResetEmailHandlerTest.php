<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\MessageHandler;

use App\Auth\Message\SendPasswordResetEmailMessage;
use App\Auth\MessageHandler\SendPasswordResetEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

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
    public function testSendsEmailWithCorrectRecipientAndResetLink(): void
    {
        $message = new SendPasswordResetEmailMessage('user@example.com', 'abc123token', 'en');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (Email $email): bool {
                self::assertSame('user@example.com', $email->getTo()[0]->getAddress());
                self::assertSame('Password Reset Request', $email->getSubject());
                self::assertStringContainsString(
                    'https://barberpro.com/en/reset-password?token=abc123token',
                    $email->getTextBody(),
                );

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testResetLinkUsesLocaleFromMessage(): void
    {
        $message = new SendPasswordResetEmailMessage('user@example.com', 'token123', 'vi');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (Email $email): bool {
                self::assertStringContainsString(
                    'https://barberpro.com/vi/reset-password?token=token123',
                    $email->getTextBody(),
                );

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testFrontendUrlTrailingSlashIsStripped(): void
    {
        $handler = new SendPasswordResetEmailHandler($this->mailer, 'https://barberpro.com/');
        $message = new SendPasswordResetEmailMessage('user@example.com', 'token', 'en');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (Email $email): bool {
                self::assertStringContainsString(
                    'https://barberpro.com/en/reset-password?token=token',
                    $email->getTextBody(),
                );
                self::assertStringNotContainsString('barberpro.com//en', $email->getTextBody());

                return true;
            }));

        ($handler)($message);
    }
}