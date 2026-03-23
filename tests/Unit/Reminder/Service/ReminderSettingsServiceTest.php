<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reminder\Service;

use App\Entity\Shop;
use App\Entity\User;
use App\Reminder\Dto\UpdateReminderSettingsRequest;
use App\Reminder\Entity\ReminderSettings;
use App\Reminder\Repository\ReminderSettingsRepository;
use App\Reminder\Service\ReminderSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReminderSettingsService::class)]
final class ReminderSettingsServiceTest extends TestCase
{
    private ReminderSettingsRepository&MockObject $repository;
    private EntityManagerInterface&MockObject $em;
    private ReminderSettingsService $sut;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ReminderSettingsRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->sut = new ReminderSettingsService($this->repository, $this->em);
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('barber@example.com');
        $user->setFirstName('Nguyen');
        $user->setLastName('Van');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    #[Test]
    public function testGetSettingsReturnsExistingSettings(): void
    {
        $shop = $this->createShop();
        $settings = new ReminderSettings();
        $settings->setShop($shop);
        $settings->setDaysSinceLastVisit(14);

        $this->repository->method('findByShopAndLocale')->with($shop, 'vi')->willReturn($settings);
        $this->em->expects(self::never())->method('persist');

        $result = $this->sut->getSettings($shop, 'vi');

        self::assertSame($settings, $result);
        self::assertSame(14, $result->getDaysSinceLastVisit());
    }

    #[Test]
    public function testGetSettingsLazyCreatesViDefaultsWhenNotFound(): void
    {
        $shop = $this->createShop();

        $this->repository->method('findByShopAndLocale')->with($shop, 'vi')->willReturn(null);
        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(ReminderSettings::class));
        $this->em->expects(self::once())->method('flush');

        $result = $this->sut->getSettings($shop, 'vi');

        self::assertSame($shop, $result->getShop());
        self::assertSame(ReminderSettings::DEFAULT_DAYS_SINCE_LAST_VISIT, $result->getDaysSinceLastVisit());
        self::assertSame(ReminderSettings::DEFAULT_MESSAGE_TEMPLATE, $result->getMessageTemplate());
        self::assertSame('vi', $result->getLocale());
    }

    #[Test]
    public function testGetSettingsLazyCreatesEnDefaultsWhenNotFound(): void
    {
        $shop = $this->createShop();

        $this->repository->method('findByShopAndLocale')->with($shop, 'en')->willReturn(null);
        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $result = $this->sut->getSettings($shop, 'en');

        self::assertSame(ReminderSettings::DEFAULT_MESSAGE_TEMPLATE_EN, $result->getMessageTemplate());
        self::assertSame('en', $result->getLocale());
    }

    #[Test]
    public function testGetSettingsUsesViLocaleByDefault(): void
    {
        $shop = $this->createShop();
        $settings = new ReminderSettings();
        $settings->setShop($shop);

        $this->repository->method('findByShopAndLocale')->with($shop, 'vi')->willReturn($settings);

        $result = $this->sut->getSettings($shop);

        self::assertSame($settings, $result);
    }

    #[Test]
    public function testGetSettingsThrowsOnConcurrentInsertWithMissingRecord(): void
    {
        $shop = $this->createShop();

        // Simulate: first findByShopAndLocale returns null (triggers creation),
        // flush throws UniqueConstraintViolationException (concurrent insert won),
        // second findByShopAndLocale also returns null (impossible race, but must not return null from the method)
        $this->repository->method('findByShopAndLocale')->willReturn(null);
        $this->em->method('flush')->willThrowException(
            $this->createMock(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class)
        );
        $this->em->expects(self::once())->method('clear');

        $this->expectException(\RuntimeException::class);

        $this->sut->getSettings($shop, 'en');
    }

    #[Test]
    public function testUpdateSettingsFlushesChanges(): void
    {
        $shop = $this->createShop();
        $settings = new ReminderSettings();
        $settings->setShop($shop);
        $settings->setLocale('vi');

        $this->repository->method('findByShopAndLocale')->willReturn($settings);
        $this->em->expects(self::once())->method('flush');

        $this->sut->updateSettings($shop, new UpdateReminderSettingsRequest(daysSinceLastVisit: 14));
    }

    #[Test]
    public function testUpdateSettingsUpdatesDaysSinceLastVisit(): void
    {
        $shop = $this->createShop();
        $settings = new ReminderSettings();
        $settings->setShop($shop);

        $this->repository->method('findByShopAndLocale')->willReturn($settings);

        $dto = new UpdateReminderSettingsRequest(daysSinceLastVisit: 14);
        $result = $this->sut->updateSettings($shop, $dto);

        self::assertSame(14, $result->getDaysSinceLastVisit());
        self::assertSame(ReminderSettings::DEFAULT_MESSAGE_TEMPLATE, $result->getMessageTemplate());
    }

    #[Test]
    public function testUpdateSettingsUpdatesMessageTemplate(): void
    {
        $shop = $this->createShop();
        $settings = new ReminderSettings();
        $settings->setShop($shop);

        $this->repository->method('findByShopAndLocale')->willReturn($settings);

        $dto = new UpdateReminderSettingsRequest(messageTemplate: 'Hello {client_name}!');
        $result = $this->sut->updateSettings($shop, $dto);

        self::assertSame(ReminderSettings::DEFAULT_DAYS_SINCE_LAST_VISIT, $result->getDaysSinceLastVisit());
        self::assertSame('Hello {client_name}!', $result->getMessageTemplate());
    }

    #[Test]
    public function testUpdateSettingsUpdatesBothFields(): void
    {
        $shop = $this->createShop();
        $settings = new ReminderSettings();
        $settings->setShop($shop);

        $this->repository->method('findByShopAndLocale')->willReturn($settings);

        $dto = new UpdateReminderSettingsRequest(daysSinceLastVisit: 7, messageTemplate: 'Hi {client_name}');
        $result = $this->sut->updateSettings($shop, $dto);

        self::assertSame(7, $result->getDaysSinceLastVisit());
        self::assertSame('Hi {client_name}', $result->getMessageTemplate());
    }

    #[Test]
    public function testUpdateSettingsSkipsNullFields(): void
    {
        $shop = $this->createShop();
        $settings = new ReminderSettings();
        $settings->setShop($shop);
        $settings->setDaysSinceLastVisit(14);
        $settings->setMessageTemplate('Custom template');

        $this->repository->method('findByShopAndLocale')->willReturn($settings);

        $dto = new UpdateReminderSettingsRequest();
        $result = $this->sut->updateSettings($shop, $dto);

        self::assertSame(14, $result->getDaysSinceLastVisit());
        self::assertSame('Custom template', $result->getMessageTemplate());
    }

    #[Test]
    public function testUpdateSettingsUsesLocaleFromDto(): void
    {
        $shop = $this->createShop();
        $settings = new ReminderSettings();
        $settings->setShop($shop);
        $settings->setLocale('en');

        $this->repository->method('findByShopAndLocale')->with($shop, 'en')->willReturn($settings);

        $dto = new UpdateReminderSettingsRequest(locale: 'en', messageTemplate: 'Hi there');
        $result = $this->sut->updateSettings($shop, $dto);

        self::assertSame('Hi there', $result->getMessageTemplate());
    }

    #[Test]
    public function testSerializeSettingsReturnsExpectedStructure(): void
    {
        $settings = new ReminderSettings();
        $settings->setShop($this->createShop());
        $settings->setDaysSinceLastVisit(14);
        $settings->setMessageTemplate('Hello');
        $settings->setLocale('en');

        $result = ReminderSettingsService::serializeSettings($settings);

        self::assertSame(14, $result['daysSinceLastVisit']);
        self::assertSame('Hello', $result['messageTemplate']);
        self::assertSame('en', $result['locale']);
        self::assertCount(3, $result);
    }
}
