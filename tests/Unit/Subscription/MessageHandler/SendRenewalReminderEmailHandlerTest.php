<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\MessageHandler;

use App\Auth\Enum\UserLocale;
use App\Entity\Shop;
use App\Entity\User;
use App\Repository\ShopRepository;
use App\Subscription\Message\SendRenewalReminderEmailMessage;
use App\Subscription\MessageHandler\SendRenewalReminderEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(SendRenewalReminderEmailHandler::class)]
final class SendRenewalReminderEmailHandlerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private ShopRepository&MockObject $shopRepository;
    private SendRenewalReminderEmailHandler $sut;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->shopRepository = $this->createMock(ShopRepository::class);

        $this->sut = new SendRenewalReminderEmailHandler(
            $this->mailer,
            $this->shopRepository,
            'noreply@barberpro.com',
            'https://app.barberpro.com',
        );
    }

    private function createShop(UserLocale $locale = UserLocale::VI): Shop
    {
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setFirstName('Nguyen');
        $user->setLastName('Van A');
        $user->setLocale($locale);

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Barber Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('barber-shop');

        return $shop;
    }

    #[Test]
    public function testSendsVietnameseRenewalReminder(): void
    {
        $shop = $this->createShop(UserLocale::VI);
        $this->shopRepository->method('find')->willReturn($shop);

        $message = new SendRenewalReminderEmailMessage(
            shopId: (string) $shop->getId(),
            endDate: new \DateTimeImmutable('2026-04-28 00:00:00', new \DateTimeZone('Asia/Ho_Chi_Minh')),
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame(['owner@example.com'], array_map(fn ($a) => $a->getAddress(), $email->getTo()));
                self::assertSame('Gói PRO của bạn sắp hết hạn - BarberPro', $email->getSubject());
                self::assertSame('emails/renewal_reminder.vi.html.twig', $email->getHtmlTemplate());

                $ctx = $email->getContext();
                self::assertSame('Barber Shop', $ctx['shopName']);
                self::assertSame('Nguyen', $ctx['ownerFirstName']);
                self::assertSame('28/04/2026', $ctx['endDate']);
                self::assertSame('https://app.barberpro.com/dashboard/subscription', $ctx['renewUrl']);

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testSendsEnglishRenewalReminder(): void
    {
        $shop = $this->createShop(UserLocale::EN);
        $this->shopRepository->method('find')->willReturn($shop);

        $message = new SendRenewalReminderEmailMessage(
            shopId: (string) $shop->getId(),
            endDate: new \DateTimeImmutable('2026-04-28', new \DateTimeZone('Asia/Ho_Chi_Minh')),
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('Your PRO subscription expires soon - BarberPro', $email->getSubject());
                self::assertSame('emails/renewal_reminder.en.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testDoesNothingWhenShopNotFound(): void
    {
        $this->shopRepository->method('find')->willReturn(null);
        $this->mailer->expects(self::never())->method('send');

        $message = new SendRenewalReminderEmailMessage(
            shopId: '00000000-0000-0000-0000-000000000001',
            endDate: new \DateTimeImmutable(),
        );

        ($this->sut)($message);
    }
}
