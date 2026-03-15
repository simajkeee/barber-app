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

        $this->repository->method('findByShop')->with($shop)->willReturn($settings);
        $this->em->expects(self::never())->method('persist');

        $result = $this->sut->getSettings($shop);

        self::assertSame($settings, $result);
        self::assertSame(14, $result->getDaysSinceLastVisit());
    }

    #[Test]
    public function testGetSettingsLazyCreatesDefaultsWhenNotFound(): void
    {
        $shop = $this->createShop();

        $this->repository->method('findByShop')->with($shop)->willReturn(null);
        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(ReminderSettings::class));
        $this->em->expects(self::once())->method('flush');

        $result = $this->sut->getSettings($shop);

        self::assertSame($shop, $result->getShop());
        self::assertSame(ReminderSettings::DEFAULT_DAYS_SINCE_LAST_VISIT, $result->getDaysSinceLastVisit());
        self::assertSame(ReminderSettings::DEFAULT_MESSAGE_TEMPLATE, $result->getMessageTemplate());
    }

    #[Test]
    public function testUpdateSettingsUpdatesDaysSinceLastVisit(): void
    {
        $shop = $this->createShop();
        $settings = new ReminderSettings();
        $settings->setShop($shop);

        $this->repository->method('findByShop')->willReturn($settings);

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

        $this->repository->method('findByShop')->willReturn($settings);

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

        $this->repository->method('findByShop')->willReturn($settings);

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

        $this->repository->method('findByShop')->willReturn($settings);

        $dto = new UpdateReminderSettingsRequest();
        $result = $this->sut->updateSettings($shop, $dto);

        self::assertSame(14, $result->getDaysSinceLastVisit());
        self::assertSame('Custom template', $result->getMessageTemplate());
    }

    #[Test]
    public function testSerializeSettingsReturnsExpectedStructure(): void
    {
        $settings = new ReminderSettings();
        $settings->setShop($this->createShop());
        $settings->setDaysSinceLastVisit(14);
        $settings->setMessageTemplate('Hello');

        $result = ReminderSettingsService::serializeSettings($settings);

        self::assertSame(14, $result['daysSinceLastVisit']);
        self::assertSame('Hello', $result['messageTemplate']);
        self::assertCount(2, $result);
    }
}
