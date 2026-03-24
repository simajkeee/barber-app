<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\MessageHandler;

use App\Notification\Message\SendNewBookingNotificationEmailMessage;
use App\Notification\MessageHandler\SendNewBookingNotificationEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(SendNewBookingNotificationEmailHandler::class)]
final class SendNewBookingNotificationEmailHandlerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private SendNewBookingNotificationEmailHandler $sut;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->sut = new SendNewBookingNotificationEmailHandler($this->mailer, 'noreply@barberpro.com');
    }

    private function createMessage(string $locale = 'vi', ?string $notes = null): SendNewBookingNotificationEmailMessage
    {
        return new SendNewBookingNotificationEmailMessage(
            ownerEmail: 'owner@example.com',
            clientFullName: 'Nguyen Van A',
            clientPhone: '0901234567',
            serviceName: 'Haircut',
            durationMinutes: 30,
            price: 150000,
            startTimeUtc: new \DateTimeImmutable('2026-03-16 03:00:00', new \DateTimeZone('UTC')),
            notes: $notes,
            locale: $locale,
        );
    }

    #[Test]
    public function testSendsToOwnerEmail(): void
    {
        $message = $this->createMessage();

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('owner@example.com', $email->getTo()[0]->getAddress());

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
                self::assertSame('Lịch hẹn mới: Nguyen Van A - Haircut', $email->getSubject());
                self::assertSame('emails/new_booking_notification.vi.html.twig', $email->getHtmlTemplate());

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
                self::assertSame('New Appointment: Nguyen Van A - Haircut', $email->getSubject());
                self::assertSame('emails/new_booking_notification.en.html.twig', $email->getHtmlTemplate());

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
        $message = $this->createMessage('vi', 'Some notes');

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                $context = $email->getContext();
                self::assertSame('Nguyen Van A', $context['clientFullName']);
                self::assertSame('0901234567', $context['clientPhone']);
                self::assertSame('Haircut', $context['serviceName']);
                self::assertSame(30, $context['durationMinutes']);
                self::assertSame(150000, $context['price']);
                self::assertSame('Some notes', $context['notes']);

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testContextNotesIsNullWhenNotProvided(): void
    {
        $message = $this->createMessage('vi', null);

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertNull($email->getContext()['notes']);

                return true;
            }));

        ($this->sut)($message);
    }
}
