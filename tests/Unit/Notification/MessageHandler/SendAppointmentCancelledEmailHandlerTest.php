<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\MessageHandler;

use App\Notification\Message\SendAppointmentCancelledEmailMessage;
use App\Notification\MessageHandler\SendAppointmentCancelledEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(SendAppointmentCancelledEmailHandler::class)]
final class SendAppointmentCancelledEmailHandlerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private SendAppointmentCancelledEmailHandler $sut;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->sut = new SendAppointmentCancelledEmailHandler($this->mailer, 'noreply@barberpro.com');
    }

    private function createMessage(string $locale = 'vi'): SendAppointmentCancelledEmailMessage
    {
        return new SendAppointmentCancelledEmailMessage(
            clientEmail: 'client@example.com',
            clientFirstName: 'Nguyen',
            serviceName: 'Haircut',
            startTimeUtc: new \DateTimeImmutable('2026-03-16 03:00:00', new \DateTimeZone('UTC')),
            shopName: 'Test Shop',
            shopPhone: '0901234567',
            locale: $locale,
        );
    }

    #[Test]
    public function testSendsToClientEmail(): void
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
    public function testVietnameseSubjectAndTemplate(): void
    {
        $message = $this->createMessage('vi');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('Lịch hẹn đã bị hủy - Test Shop', $email->getSubject());
                self::assertSame('emails/appointment_cancelled.vi.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testEnglishSubjectAndTemplate(): void
    {
        $message = $this->createMessage('en');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('Appointment Cancelled - Test Shop', $email->getSubject());
                self::assertSame('emails/appointment_cancelled.en.html.twig', $email->getHtmlTemplate());

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
                self::assertSame('Asia/Ho_Chi_Minh', $startTime->getTimezone()->getName());
                self::assertSame('10:00', $startTime->format('H:i'));

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testContextContainsAllVariables(): void
    {
        $message = $this->createMessage();

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                $context = $email->getContext();
                self::assertSame('Nguyen', $context['clientFirstName']);
                self::assertSame('Haircut', $context['serviceName']);
                self::assertSame('Test Shop', $context['shopName']);
                self::assertSame('0901234567', $context['shopPhone']);

                return true;
            }));

        ($this->sut)($message);
    }
}
