<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shop\Service;

use App\Common\Exception\ApiException;
use App\Entity\Shop;
use App\Entity\User;
use App\Entity\WorkSchedule;
use App\Repository\ShopRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Dto\CreateShopRequest;
use App\Shop\Dto\ScheduleEntry;
use App\Shop\Dto\UpdateScheduleRequest;
use App\Shop\Dto\UpdateShopRequest;
use App\Shop\Enum\DayOfWeek;
use App\Shop\Service\ShopManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShopManager::class)]
final class ShopManagerTest extends TestCase
{
    private ShopRepository&MockObject $shopRepository;
    private WorkScheduleRepository&MockObject $workScheduleRepository;
    private EntityManagerInterface&MockObject $em;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private ShopManager $sut;

    protected function setUp(): void
    {
        $this->shopRepository = $this->createMock(ShopRepository::class);
        $this->workScheduleRepository = $this->createMock(WorkScheduleRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->sut = new ShopManager(
            $this->shopRepository,
            $this->workScheduleRepository,
            $this->em,
            $this->eventDispatcher,
        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('barber@example.com');
        $user->setFirstName('Nguyen');
        $user->setLastName('Van');

        return $user;
    }

    private function createShopEntity(User $user, string $slug = 'test-shop'): Shop
    {
        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug($slug);

        return $shop;
    }

    private function buildFullScheduleEntries(): array
    {
        $entries = [];
        foreach (DayOfWeek::cases() as $day) {
            $isSunday = $day === DayOfWeek::SUNDAY;
            $entries[] = new ScheduleEntry(
                dayOfWeek: $day,
                openTime: $isSunday ? null : '09:00',
                closeTime: $isSunday ? null : '18:00',
                isOpen: !$isSunday,
            );
        }

        return $entries;
    }

    // --- createShop ---

    #[Test]
    public function testCreateShopSuccessReturnsShopWithSlugAndSchedule(): void
    {
        $user = $this->createUser();
        $dto = new CreateShopRequest(
            name: 'Tiệm Tóc Đẹp',
            address: '123 Nguyễn Huệ',
            phone: '0901234567',
            description: 'Best barbershop',
        );

        $this->shopRepository->method('findByOwner')->willReturn(null);
        $this->shopRepository->method('findBySlug')->willReturn(null);

        // 1 shop + 7 schedules = 8 persists
        $this->em->expects(self::exactly(8))->method('persist');
        $this->em->expects(self::once())->method('flush');

        $shop = $this->sut->createShop($user, $dto);

        self::assertSame('Tiệm Tóc Đẹp', $shop->getName());
        self::assertSame('123 Nguyễn Huệ', $shop->getAddress());
        self::assertSame('0901234567', $shop->getPhone());
        self::assertSame('Best barbershop', $shop->getDescription());
        self::assertSame($user, $shop->getOwner());
        self::assertNotEmpty($shop->getSlug());
    }

    #[Test]
    public function testCreateShopWithNullDescription(): void
    {
        $user = $this->createUser();
        $dto = new CreateShopRequest(name: 'Shop', address: 'Addr', phone: '123');

        $this->shopRepository->method('findByOwner')->willReturn(null);
        $this->shopRepository->method('findBySlug')->willReturn(null);

        $shop = $this->sut->createShop($user, $dto);

        self::assertNull($shop->getDescription());
    }

    #[Test]
    public function testCreateShopThrows409WhenUserAlreadyHasShop(): void
    {
        $user = $this->createUser();
        $existingShop = $this->createShopEntity($user);
        $dto = new CreateShopRequest(name: 'New Shop', address: 'Addr', phone: '123');

        $this->shopRepository->method('findByOwner')->willReturn($existingShop);

        try {
            $this->sut->createShop($user, $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('SHOP_ALREADY_EXISTS', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateShopCatchesUniqueConstraintViolation(): void
    {
        $user = $this->createUser();
        $dto = new CreateShopRequest(name: 'Shop', address: 'Addr', phone: '123');

        $this->shopRepository->method('findByOwner')->willReturn(null);
        $this->shopRepository->method('findBySlug')->willReturn(null);

        $this->em->method('flush')->willThrowException(
            $this->createMock(UniqueConstraintViolationException::class),
        );

        try {
            $this->sut->createShop($user, $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('SHOP_ALREADY_EXISTS', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateShopPersistsShopThenSevenScheduleRows(): void
    {
        $user = $this->createUser();
        $dto = new CreateShopRequest(name: 'Shop', address: 'Addr', phone: '123');

        $this->shopRepository->method('findByOwner')->willReturn(null);
        $this->shopRepository->method('findBySlug')->willReturn(null);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $this->sut->createShop($user, $dto);

        self::assertCount(8, $persisted);
        self::assertInstanceOf(Shop::class, $persisted[0]);

        $scheduleEntities = array_slice($persisted, 1);
        self::assertCount(7, $scheduleEntities);

        $days = [];
        $sundayFound = false;
        foreach ($scheduleEntities as $ws) {
            self::assertInstanceOf(WorkSchedule::class, $ws);
            $days[] = $ws->getDayOfWeek();

            if ($ws->getDayOfWeek() === DayOfWeek::SUNDAY) {
                self::assertFalse($ws->isOpen());
                self::assertNull($ws->getOpenTime());
                self::assertNull($ws->getCloseTime());
                $sundayFound = true;
            } else {
                self::assertTrue($ws->isOpen());
                self::assertSame('09:00', $ws->getOpenTime()->format('H:i'));
                self::assertSame('19:00', $ws->getCloseTime()->format('H:i'));
            }
        }

        self::assertTrue($sundayFound);
        self::assertCount(7, array_unique(array_map(fn (DayOfWeek $d) => $d->value, $days)));
    }

    // --- generateSlug ---

    #[Test]
    public function testGenerateSlugTransliteratesVietnamese(): void
    {
        $this->shopRepository->method('findBySlug')->willReturn(null);

        $slug = $this->sut->generateSlug('Tiệm Tóc Đẹp');

        self::assertSame('tiem-toc-dep', $slug);
    }

    #[Test]
    public function testGenerateSlugLowercasesAndReplaceSpaces(): void
    {
        $this->shopRepository->method('findBySlug')->willReturn(null);

        $slug = $this->sut->generateSlug('My Cool Shop');

        self::assertSame('my-cool-shop', $slug);
    }

    #[Test]
    public function testGenerateSlugAppendsSuffixOnCollision(): void
    {
        $existingShop = $this->createShopEntity($this->createUser(), 'my-shop');

        $this->shopRepository->method('findBySlug')
            ->willReturnCallback(function (string $slug) use ($existingShop): ?Shop {
                return $slug === 'my-shop' ? $existingShop : null;
            });

        $slug = $this->sut->generateSlug('My Shop');

        self::assertStringStartsWith('my-shop-', $slug);
        self::assertMatchesRegularExpression('/^my-shop-[a-f0-9]{4}$/', $slug);
    }

    // --- updateShop ---

    #[Test]
    public function testUpdateShopPartialUpdateOnlyChangesProvidedFields(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user);
        $shop->setDescription('Old description');
        $shop->setCoverImageUrl('https://old.jpg');

        $dto = new UpdateShopRequest(name: 'New Name');

        $result = $this->sut->updateShop($shop, $dto, ['name']);

        self::assertSame('New Name', $result->getName());
        self::assertSame('123 Street', $result->getAddress());
        self::assertSame('0901234567', $result->getPhone());
        self::assertSame('Old description', $result->getDescription());
        self::assertSame('https://old.jpg', $result->getCoverImageUrl());
    }

    #[Test]
    public function testUpdateShopClearsDescriptionWhenExplicitlyNull(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user);
        $shop->setDescription('Some description');

        $dto = new UpdateShopRequest(description: null);

        $result = $this->sut->updateShop($shop, $dto, ['description']);

        self::assertNull($result->getDescription());
    }

    #[Test]
    public function testUpdateShopPreservesDescriptionWhenNotInExplicitFields(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user);
        $shop->setDescription('Keep this');

        $dto = new UpdateShopRequest(name: 'New Name');

        $result = $this->sut->updateShop($shop, $dto, ['name']);

        self::assertSame('Keep this', $result->getDescription());
    }

    #[Test]
    public function testUpdateShopClearsCoverImageUrlWhenExplicitlyNull(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user);
        $shop->setCoverImageUrl('https://cover.jpg');

        $dto = new UpdateShopRequest(coverImageUrl: null);

        $result = $this->sut->updateShop($shop, $dto, ['coverImageUrl']);

        self::assertNull($result->getCoverImageUrl());
    }

    #[Test]
    public function testUpdateShopSlugChangeSuccess(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user, 'old-slug');

        $this->shopRepository->method('findBySlug')->willReturn(null);

        $dto = new UpdateShopRequest(slug: 'new-slug');

        $result = $this->sut->updateShop($shop, $dto, ['slug']);

        self::assertSame('new-slug', $result->getSlug());
    }

    #[Test]
    public function testUpdateShopSlugSetToSameValueDoesNotThrow(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user, 'my-slug');

        $this->shopRepository->method('findBySlug')->willReturn($shop);

        $dto = new UpdateShopRequest(slug: 'my-slug');

        $result = $this->sut->updateShop($shop, $dto, ['slug']);

        self::assertSame('my-slug', $result->getSlug());
    }

    #[Test]
    public function testUpdateShopThrows409WhenSlugTakenByAnotherShop(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user, 'my-slug');

        $otherShop = $this->createShopEntity($this->createUser(), 'taken-slug');

        $this->shopRepository->method('findBySlug')->willReturn($otherShop);

        $dto = new UpdateShopRequest(slug: 'taken-slug');

        try {
            $this->sut->updateShop($shop, $dto, ['slug']);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('SLUG_ALREADY_EXISTS', $e->errorCode);
        }
    }

    #[Test]
    public function testUpdateShopCatchesUniqueConstraintOnSlug(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user);

        $this->shopRepository->method('findBySlug')->willReturn(null);
        $this->em->method('flush')->willThrowException(
            $this->createMock(UniqueConstraintViolationException::class),
        );

        $dto = new UpdateShopRequest(slug: 'race-condition-slug');

        try {
            $this->sut->updateShop($shop, $dto, ['slug']);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('SLUG_ALREADY_EXISTS', $e->errorCode);
        }
    }

    // --- updateSchedule ---

    #[Test]
    public function testUpdateScheduleReplacesAllSevenDays(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user);
        $entries = $this->buildFullScheduleEntries();
        $dto = new UpdateScheduleRequest($entries);

        $this->em->method('wrapInTransaction')->willReturnCallback(function (callable $fn) {
            $fn();
        });

        $this->workScheduleRepository->expects(self::once())->method('deleteByShop')->with($shop);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $result = $this->sut->updateSchedule($shop, $dto);

        self::assertCount(7, $result);
        self::assertCount(7, $persisted);

        foreach ($result as $ws) {
            self::assertInstanceOf(WorkSchedule::class, $ws);
            self::assertSame($shop, $ws->getShop());
        }
    }

    #[Test]
    public function testUpdateScheduleOpenDayHasTimes(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user);
        $entries = $this->buildFullScheduleEntries();
        $dto = new UpdateScheduleRequest($entries);

        $this->em->method('wrapInTransaction')->willReturnCallback(fn (callable $fn) => $fn());

        $result = $this->sut->updateSchedule($shop, $dto);

        $monday = null;
        foreach ($result as $ws) {
            if ($ws->getDayOfWeek() === DayOfWeek::MONDAY) {
                $monday = $ws;
                break;
            }
        }

        self::assertNotNull($monday);
        self::assertTrue($monday->isOpen());
        self::assertSame('09:00', $monday->getOpenTime()->format('H:i'));
        self::assertSame('18:00', $monday->getCloseTime()->format('H:i'));
    }

    #[Test]
    public function testUpdateScheduleClosedDayHasNullTimes(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user);
        $entries = $this->buildFullScheduleEntries();
        $dto = new UpdateScheduleRequest($entries);

        $this->em->method('wrapInTransaction')->willReturnCallback(fn (callable $fn) => $fn());

        $result = $this->sut->updateSchedule($shop, $dto);

        $sunday = null;
        foreach ($result as $ws) {
            if ($ws->getDayOfWeek() === DayOfWeek::SUNDAY) {
                $sunday = $ws;
                break;
            }
        }

        self::assertNotNull($sunday);
        self::assertFalse($sunday->isOpen());
        self::assertNull($sunday->getOpenTime());
        self::assertNull($sunday->getCloseTime());
    }

    #[Test]
    public function testUpdateScheduleThrows400WhenDuplicateDay(): void
    {
        $entries = [
            new ScheduleEntry(DayOfWeek::MONDAY, '09:00', '18:00', true),
            new ScheduleEntry(DayOfWeek::MONDAY, '10:00', '19:00', true),
            new ScheduleEntry(DayOfWeek::TUESDAY, '09:00', '18:00', true),
            new ScheduleEntry(DayOfWeek::WEDNESDAY, '09:00', '18:00', true),
            new ScheduleEntry(DayOfWeek::THURSDAY, '09:00', '18:00', true),
            new ScheduleEntry(DayOfWeek::FRIDAY, '09:00', '18:00', true),
            new ScheduleEntry(DayOfWeek::SATURDAY, '09:00', '18:00', true),
        ];
        $dto = new UpdateScheduleRequest($entries);
        $shop = $this->createShopEntity($this->createUser());

        try {
            $this->sut->updateSchedule($shop, $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('VALIDATION_ERROR', $e->errorCode);
            self::assertStringContainsString('Duplicate day', $e->getMessage());
        }
    }

    #[Test]
    public function testUpdateScheduleThrows400WhenFewerThan7Days(): void
    {
        $entries = [
            new ScheduleEntry(DayOfWeek::MONDAY, '09:00', '18:00', true),
            new ScheduleEntry(DayOfWeek::TUESDAY, '09:00', '18:00', true),
        ];
        $dto = new UpdateScheduleRequest($entries);
        $shop = $this->createShopEntity($this->createUser());

        try {
            $this->sut->updateSchedule($shop, $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('VALIDATION_ERROR', $e->errorCode);
            self::assertStringContainsString('7 days', $e->getMessage());
        }
    }

    #[Test]
    public function testUpdateScheduleThrows400WhenOpenTimeAfterCloseTime(): void
    {
        $entries = [];
        foreach (DayOfWeek::cases() as $day) {
            if ($day === DayOfWeek::MONDAY) {
                $entries[] = new ScheduleEntry($day, '19:00', '09:00', true);
            } else {
                $entries[] = new ScheduleEntry($day, '09:00', '18:00', true);
            }
        }
        $dto = new UpdateScheduleRequest($entries);
        $shop = $this->createShopEntity($this->createUser());

        try {
            $this->sut->updateSchedule($shop, $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('VALIDATION_ERROR', $e->errorCode);
            self::assertStringContainsString('before closing', $e->getMessage());
        }
    }

    #[Test]
    public function testUpdateScheduleThrows400WhenOpenTimeEqualsCloseTime(): void
    {
        $entries = [];
        foreach (DayOfWeek::cases() as $day) {
            if ($day === DayOfWeek::MONDAY) {
                $entries[] = new ScheduleEntry($day, '09:00', '09:00', true);
            } else {
                $entries[] = new ScheduleEntry($day, '09:00', '18:00', true);
            }
        }
        $dto = new UpdateScheduleRequest($entries);
        $shop = $this->createShopEntity($this->createUser());

        try {
            $this->sut->updateSchedule($shop, $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('VALIDATION_ERROR', $e->errorCode);
        }
    }

    #[Test]
    public function testUpdateScheduleThrows400WhenOpenDayMissingTimes(): void
    {
        $entries = [];
        foreach (DayOfWeek::cases() as $day) {
            if ($day === DayOfWeek::MONDAY) {
                $entries[] = new ScheduleEntry($day, null, null, true);
            } else {
                $entries[] = new ScheduleEntry($day, '09:00', '18:00', true);
            }
        }
        $dto = new UpdateScheduleRequest($entries);
        $shop = $this->createShopEntity($this->createUser());

        try {
            $this->sut->updateSchedule($shop, $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertStringContainsString('required when the shop is open', $e->getMessage());
        }
    }

    // --- getShopForUser ---

    #[Test]
    public function testGetShopForUserReturnsShopWhenExists(): void
    {
        $user = $this->createUser();
        $shop = $this->createShopEntity($user);

        $this->shopRepository->method('findByOwner')->with($user)->willReturn($shop);

        self::assertSame($shop, $this->sut->getShopForUser($user));
    }

    #[Test]
    public function testGetShopForUserReturnsNullWhenNoShop(): void
    {
        $user = $this->createUser();
        $this->shopRepository->method('findByOwner')->willReturn(null);

        self::assertNull($this->sut->getShopForUser($user));
    }

    // --- getSchedule ---

    #[Test]
    public function testGetScheduleDelegatesToRepository(): void
    {
        $shop = $this->createShopEntity($this->createUser());
        $schedules = [new WorkSchedule(), new WorkSchedule()];

        $this->workScheduleRepository->method('findByShop')->with($shop)->willReturn($schedules);

        self::assertSame($schedules, $this->sut->getSchedule($shop));
    }

    // --- serializeShop ---

    #[Test]
    public function testSerializeShopReturnsExpectedStructure(): void
    {
        $shop = $this->createShopEntity($this->createUser(), 'my-slug');
        $shop->setDescription('A shop');
        $shop->setCoverImageUrl('https://cover.jpg');

        $ws = new WorkSchedule();
        $ws->setShop($shop);
        $ws->setDayOfWeek(DayOfWeek::MONDAY);
        $ws->setIsOpen(true);
        $ws->setOpenTime(new \DateTimeImmutable('09:00'));
        $ws->setCloseTime(new \DateTimeImmutable('18:00'));

        $result = ShopManager::serializeShop($shop, [$ws]);

        self::assertSame('Test Shop', $result['name']);
        self::assertSame('123 Street', $result['address']);
        self::assertSame('0901234567', $result['phone']);
        self::assertSame('A shop', $result['description']);
        self::assertSame('my-slug', $result['slug']);
        self::assertSame('https://cover.jpg', $result['coverImageUrl']);
        self::assertIsString($result['id']);
        self::assertIsString($result['createdAt']);
        self::assertIsString($result['updatedAt']);
        self::assertCount(1, $result['schedule']);
    }

    // --- serializeScheduleEntry ---

    #[Test]
    public function testSerializeScheduleEntryOpenDay(): void
    {
        $ws = new WorkSchedule();
        $ws->setDayOfWeek(DayOfWeek::TUESDAY);
        $ws->setIsOpen(true);
        $ws->setOpenTime(new \DateTimeImmutable('10:00'));
        $ws->setCloseTime(new \DateTimeImmutable('20:00'));

        $result = ShopManager::serializeScheduleEntry($ws);

        self::assertSame('tuesday', $result['dayOfWeek']);
        self::assertSame('10:00', $result['openTime']);
        self::assertSame('20:00', $result['closeTime']);
        self::assertTrue($result['isOpen']);
    }

    #[Test]
    public function testSerializeScheduleEntryClosedDay(): void
    {
        $ws = new WorkSchedule();
        $ws->setDayOfWeek(DayOfWeek::SUNDAY);
        $ws->setIsOpen(false);

        $result = ShopManager::serializeScheduleEntry($ws);

        self::assertSame('sunday', $result['dayOfWeek']);
        self::assertNull($result['openTime']);
        self::assertNull($result['closeTime']);
        self::assertFalse($result['isOpen']);
    }
}