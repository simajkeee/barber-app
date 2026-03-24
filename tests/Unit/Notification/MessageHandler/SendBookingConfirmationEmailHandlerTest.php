<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\MessageHandler;

use App\Notification\Message\SendBookingConfirmationEmailMessage;
use App\Notification\MessageHandler\SendBookingConfirmationEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(SendBookingConfirmationEmailHandler::class)]
final class SendBookingConfirmationEmailHandlerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private SendBookingConfirmationEmailHandler $sut;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->sut = new SendBookingConfirmationEmailHandler($this->mailer, 'noreply@barberpro.com');
    }

    private function createMessage(string $locale = 'vi'): SendBookingConfirmationEmailMessage
    {
        return new SendBookingConfirmationEmailMessage(
            clientEmail: 'client@example.com',
            clientFirstName: 'Nguyen',
            serviceName: 'Haircut',
            durationMinutes: 30,
            startTimeUtc: new \DateTimeImmutable('2026-03-16 03:00:00', new \DateTimeZone('UTC')),
            shopName: 'Test Shop',
            shopAddress: '123 Street',
            shopPhone: '0901234567',
            locale: $locale,
        );
    }

    #[Test]
    public function testSendsEmailToCorrectRecipient(): void
    {
        $message = $this->createMessage();

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('client@example.com', $email->getTo()[0]->getAddress());

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testUsesVietnameseTemplateAndSubject(): void
    {
        $message = $this->createMessage('vi');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('Xác nhận lịch hẹn - Test Shop', $email->getSubject());
                self::assertSame('emails/booking_confirmation.vi.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testUsesEnglishTemplateAndSubject(): void
    {
        $message = $this->createMessage('en');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('Booking Confirmation - Test Shop', $email->getSubject());
                self::assertSame('emails/booking_confirmation.en.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testConvertsStartTimeToHoChiMinhTimezone(): void
    {
        $message = $this->createMessage();

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                $context = $email->getContext();
                $startTime = $context['startTime'];
                self::assertInstanceOf(\DateTimeImmutable::class, $startTime);
                self::assertSame('Asia/Ho_Chi_Minh', $startTime->getTimezone()->getName());
                // UTC 03:00 → HCM 10:00
                self::assertSame('10:00', $startTime->format('H:i'));

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testContextContainsAllRequiredVariables(): void
    {
        $message = $this->createMessage();

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                $context = $email->getContext();
                self::assertSame('Nguyen', $context['clientFirstName']);
                self::assertSame('Haircut', $context['serviceName']);
                self::assertSame(30, $context['durationMinutes']);
                self::assertSame('Test Shop', $context['shopName']);
                self::assertSame('123 Street', $context['shopAddress']);
                self::assertSame('0901234567', $context['shopPhone']);

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testSendsFromConfiguredAddress(): void
    {
        $message = $this->createMessage();

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('noreply@barberpro.com', $email->getFrom()[0]->getAddress());

                return true;
            }));

        ($this->sut)($message);
    }
}
