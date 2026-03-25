<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shop\Service;

use App\Entity\Shop;
use App\Entity\ShopService;
use App\Entity\User;
use App\Repository\ShopServiceRepository;
use App\Shop\Dto\CreateServiceRequest;
use App\Shop\Dto\UpdateServiceRequest;
use App\Shop\Service\ShopServiceManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShopServiceManager::class)]
final class ShopServiceManagerTest extends TestCase
{
    private ShopServiceRepository&MockObject $shopServiceRepository;
    private EntityManagerInterface&MockObject $em;
    private ShopServiceManager $sut;

    protected function setUp(): void
    {
        $this->shopServiceRepository = $this->createMock(ShopServiceRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->sut = new ShopServiceManager(
            $this->shopServiceRepository,
            $this->em,
        );
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('barber@example.com');
        $user->setFirstName('A');
        $user->setLastName('B');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Street');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }

    private function createShopServiceEntity(Shop $shop): ShopService
    {
        $service = new ShopService();
        $service->setShop($shop);
        $service->setName('Haircut');
        $service->setDurationMinutes(30);
        $service->setPrice(100000);
        $service->setSortOrder(0);

        return $service;
    }

    // --- createService ---

    #[Test]
    public function testCreateServicePersistsAndReturnsService(): void
    {
        $shop = $this->createShop();
        $dto = new CreateServiceRequest(
            name: 'Premium Cut',
            durationMinutes: 45,
            price: 150000,
            sortOrder: 1,
        );

        $this->em->expects(self::once())->method('persist')->willReturnCallback(
            function (ShopService $entity) use ($shop): void {
                self::assertSame($shop, $entity->getShop());
                self::assertSame('Premium Cut', $entity->getName());
                self::assertSame(45, $entity->getDurationMinutes());
                self::assertSame(150000, $entity->getPrice());
                self::assertSame(1, $entity->getSortOrder());
                self::assertTrue($entity->isActive());
            },
        );
        $this->em->expects(self::once())->method('flush');

        $result = $this->sut->createService($shop, $dto);

        self::assertSame('Premium Cut', $result->getName());
        self::assertSame(45, $result->getDurationMinutes());
        self::assertSame(150000, $result->getPrice());
        self::assertSame(1, $result->getSortOrder());
    }

    #[Test]
    public function testCreateServiceDefaultSortOrder(): void
    {
        $shop = $this->createShop();
        $dto = new CreateServiceRequest(name: 'Cut', durationMinutes: 30, price: 50000);

        $result = $this->sut->createService($shop, $dto);

        self::assertSame(0, $result->getSortOrder());
    }

    // --- updateService ---

    #[Test]
    public function testUpdateServicePartialUpdateOnlyName(): void
    {
        $shop = $this->createShop();
        $service = $this->createShopServiceEntity($shop);
        $dto = new UpdateServiceRequest(name: 'New Haircut');

        $this->em->expects(self::once())->method('flush');

        $result = $this->sut->updateService($service, $dto);

        self::assertSame('New Haircut', $result->getName());
        self::assertSame(30, $result->getDurationMinutes());
        self::assertSame(100000, $result->getPrice());
        self::assertTrue($result->isActive());
        self::assertSame(0, $result->getSortOrder());
    }

    #[Test]
    public function testUpdateServiceAllFields(): void
    {
        $shop = $this->createShop();
        $service = $this->createShopServiceEntity($shop);
        $dto = new UpdateServiceRequest(
            name: 'Deluxe Cut',
            durationMinutes: 60,
            price: 200000,
            isActive: false,
            sortOrder: 5,
        );

        $result = $this->sut->updateService($service, $dto);

        self::assertSame('Deluxe Cut', $result->getName());
        self::assertSame(60, $result->getDurationMinutes());
        self::assertSame(200000, $result->getPrice());
        self::assertFalse($result->isActive());
        self::assertSame(5, $result->getSortOrder());
    }

    #[Test]
    public function testUpdateServiceNoFieldsDoesNotChangeAnything(): void
    {
        $shop = $this->createShop();
        $service = $this->createShopServiceEntity($shop);
        $dto = new UpdateServiceRequest();

        $this->em->expects(self::once())->method('flush');

        $result = $this->sut->updateService($service, $dto);

        self::assertSame('Haircut', $result->getName());
        self::assertSame(30, $result->getDurationMinutes());
        self::assertSame(100000, $result->getPrice());
        self::assertTrue($result->isActive());
    }

    // --- deleteService ---

    #[Test]
    public function testDeleteServiceSetsIsActiveFalse(): void
    {
        $shop = $this->createShop();
        $service = $this->createShopServiceEntity($shop);

        self::assertTrue($service->isActive());

        $this->em->expects(self::once())->method('flush');

        $this->sut->deleteService($service);

        self::assertFalse($service->isActive());
    }

    // --- listServices ---

    #[Test]
    public function testListServicesDelegatesToRepository(): void
    {
        $shop = $this->createShop();
        $services = [$this->createShopServiceEntity($shop)];

        $this->shopServiceRepository->expects(self::once())
            ->method('findByShop')
            ->with($shop, false)
            ->willReturn($services);

        $result = $this->sut->listServices($shop);

        self::assertSame($services, $result);
    }

    #[Test]
    public function testListServicesPassesIncludeInactiveFlag(): void
    {
        $shop = $this->createShop();

        $this->shopServiceRepository->expects(self::once())
            ->method('findByShop')
            ->with($shop, true)
            ->willReturn([]);

        $this->sut->listServices($shop, true);
    }

    // --- serializeService ---

    #[Test]
    public function testSerializeServiceReturnsExpectedStructure(): void
    {
        $shop = $this->createShop();
        $service = $this->createShopServiceEntity($shop);

        $result = ShopServiceManager::serializeService($service);

        self::assertIsString($result['id']);
        self::assertSame('Haircut', $result['name']);
        self::assertSame(30, $result['durationMinutes']);
        self::assertSame(100000, $result['price']);
        self::assertTrue($result['isActive']);
        self::assertSame(0, $result['sortOrder']);
        self::assertIsString($result['createdAt']);
        self::assertIsString($result['updatedAt']);
        self::assertCount(8, $result);
    }

    #[Test]
    public function testSerializeServiceInactiveService(): void
    {
        $shop = $this->createShop();
        $service = $this->createShopServiceEntity($shop);
        $service->setIsActive(false);

        $result = ShopServiceManager::serializeService($service);

        self::assertFalse($result['isActive']);
    }
}
