<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reminder\MessageHandler;

use App\Reminder\Message\SendReminderEmailMessage;
use App\Reminder\MessageHandler\SendReminderEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(SendReminderEmailHandler::class)]
final class SendReminderEmailHandlerTest extends TestCase
{
    private MailerInterface&\PHPUnit\Framework\MockObject\MockObject $mailer;
    private SendReminderEmailHandler $handler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->handler = new SendReminderEmailHandler($this->mailer, 'https://barberpro.com', 'noreply@barberpro.com');
    }

    private function createMessage(string $locale = 'vi'): SendReminderEmailMessage
    {
        return new SendReminderEmailMessage(
            clientId: '550e8400-e29b-41d4-a716-446655440000',
            shopId: '660e8400-e29b-41d4-a716-446655440000',
            clientEmail: 'client@example.com',
            clientFirstName: 'Nguyen',
            shopName: 'Test Shop',
            shopPhone: '0901234567',
            messageText: 'Hello Nguyen, time for a haircut!',
            optOutToken: str_repeat('ab', 32),
            locale: $locale,
        );
    }

    #[Test]
    public function testSendsEmailWithViTemplate(): void
    {
        $message = $this->createMessage('vi');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                self::assertSame(['client@example.com'], array_map(fn ($a) => $a->getAddress(), $email->getTo()));
                self::assertSame('Nhắc lịch hẹn', $email->getSubject());
                self::assertSame('emails/reminder.vi.html.twig', $email->getHtmlTemplate());

                $context = $email->getContext();
                self::assertSame('Nguyen', $context['clientFirstName']);
                self::assertSame('Test Shop', $context['shopName']);
                self::assertSame('0901234567', $context['shopPhone']);
                self::assertSame('Hello Nguyen, time for a haircut!', $context['messageText']);
                self::assertStringContainsString('vi/opt-out?token=', $context['optOutUrl']);
                self::assertSame('vi', $context['locale']);

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testSendsEmailWithEnTemplate(): void
    {
        $message = $this->createMessage('en');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) {
                self::assertSame('Appointment Reminder', $email->getSubject());
                self::assertSame('emails/reminder.en.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testOptOutUrlContainsToken(): void
    {
        $message = $this->createMessage();
        $expectedToken = str_repeat('ab', 32);

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) use ($expectedToken) {
                $url = $email->getContext()['optOutUrl'];
                self::assertStringContainsString($expectedToken, $url);
                self::assertStringStartsWith('https://barberpro.com/', $url);

                return true;
            }));

        ($this->handler)($message);
    }

    #[Test]
    public function testDoesNotMarkClientAsReminded(): void
    {
        $message = $this->createMessage();

        $this->mailer->expects(self::once())->method('send');

        // Handler should only send email, nothing else
        ($this->handler)($message);

        // No EntityManager interaction — handler has no EM dependency
        self::assertTrue(true);
    }
}
