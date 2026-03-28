<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscription\MessageHandler;

use App\Auth\Enum\UserLocale;
use App\Entity\Shop;
use App\Entity\User;
use App\Repository\ShopRepository;
use App\Subscription\Message\SendPaymentSuccessEmailMessage;
use App\Subscription\MessageHandler\SendPaymentSuccessEmailHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(SendPaymentSuccessEmailHandler::class)]
final class SendPaymentSuccessEmailHandlerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private ShopRepository&MockObject $shopRepository;
    private SendPaymentSuccessEmailHandler $sut;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->shopRepository = $this->createMock(ShopRepository::class);

        $this->sut = new SendPaymentSuccessEmailHandler(
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
    public function testSendsVietnameseEmailForViLocale(): void
    {
        $shop = $this->createShop(UserLocale::VI);
        $this->shopRepository->method('find')->willReturn($shop);

        $message = new SendPaymentSuccessEmailMessage(
            shopId: (string) $shop->getId(),
            transId: '3568107123456',
            amountVnd: 299000,
            endDate: new \DateTimeImmutable('2026-05-01 00:00:00', new \DateTimeZone('Asia/Ho_Chi_Minh')),
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame(['owner@example.com'], array_map(fn ($a) => $a->getAddress(), $email->getTo()));
                self::assertSame('Thanh toán thành công - BarberPro PRO', $email->getSubject());
                self::assertSame('emails/payment_success.vi.html.twig', $email->getHtmlTemplate());

                $ctx = $email->getContext();
                self::assertSame('Barber Shop', $ctx['shopName']);
                self::assertSame('Nguyen', $ctx['ownerFirstName']);
                self::assertSame('3568107123456', $ctx['transId']);
                self::assertSame(299000, $ctx['amountVnd']);
                self::assertSame('01/05/2026', $ctx['endDate']);
                self::assertSame('https://app.barberpro.com/dashboard/subscription', $ctx['dashboardUrl']);

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testSendsEnglishEmailForEnLocale(): void
    {
        $shop = $this->createShop(UserLocale::EN);
        $this->shopRepository->method('find')->willReturn($shop);

        $message = new SendPaymentSuccessEmailMessage(
            shopId: (string) $shop->getId(),
            transId: 'tx-123',
            amountVnd: 299000,
            endDate: new \DateTimeImmutable('2026-05-01', new \DateTimeZone('Asia/Ho_Chi_Minh')),
        );

        $this->mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('Payment Successful - BarberPro PRO', $email->getSubject());
                self::assertSame('emails/payment_success.en.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        ($this->sut)($message);
    }

    #[Test]
    public function testDoesNothingWhenShopNotFound(): void
    {
        $this->shopRepository->method('find')->willReturn(null);
        $this->mailer->expects(self::never())->method('send');

        $message = new SendPaymentSuccessEmailMessage(
            shopId: '00000000-0000-0000-0000-000000000001',
            transId: 'tx-1',
            amountVnd: 299000,
            endDate: new \DateTimeImmutable(),
        );

        ($this->sut)($message);
    }
}
