<?php

declare(strict_types=1);

namespace App\Tests\Unit\PublicBooking\Service;

use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\User;
use App\PublicBooking\Service\ClientResolverService;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientResolverService::class)]
final class ClientResolverServiceTest extends TestCase
{
    private ClientRepository&MockObject $clientRepository;
    private EntityManagerInterface&MockObject $em;
    private ClientResolverService $sut;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->sut = new ClientResolverService(
            $this->clientRepository,
            $this->em,
        );
    }

    #[Test]
    public function resolveClientReturnsExistingClientWhenPhoneMatches(): void
    {
        $shop = $this->createShop();
        $existingClient = new Client();
        $existingClient->setShop($shop);
        $existingClient->setFirstName('Old');
        $existingClient->setLastName('Name');
        $existingClient->setPhone('0901234567');

        $this->clientRepository->method('findByShopAndPhone')
            ->with($shop, '0901234567')
            ->willReturn($existingClient);

        $this->em->expects(self::never())->method('persist');

        $result = $this->sut->resolveClient($shop, 'Old Name', '0901234567');

        self::assertSame($existingClient, $result);
    }

    #[Test]
    public function resolveClientUpdatesNameWhenDifferent(): void
    {
        $shop = $this->createShop();
        $existingClient = new Client();
        $existingClient->setShop($shop);
        $existingClient->setFirstName('Old');
        $existingClient->setLastName('Name');
        $existingClient->setPhone('0901234567');

        $this->clientRepository->method('findByShopAndPhone')
            ->willReturn($existingClient);

        $result = $this->sut->resolveClient($shop, 'Nguyen Van A', '0901234567');

        self::assertSame('Nguyen', $result->getFirstName());
        self::assertSame('Van A', $result->getLastName());
    }

    #[Test]
    public function resolveClientCreatesNewClientWhenPhoneNotFound(): void
    {
        $shop = $this->createShop();

        $this->clientRepository->method('findByShopAndPhone')
            ->willReturn(null);

        $this->em->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(Client::class));

        $result = $this->sut->resolveClient($shop, 'Nguyen Van B', '0909876543');

        self::assertSame('Nguyen', $result->getFirstName());
        self::assertSame('Van B', $result->getLastName());
        self::assertSame('0909876543', $result->getPhone());
        self::assertSame($shop, $result->getShop());
    }

    #[Test]
    public function resolveClientHandlesSingleNameCorrectly(): void
    {
        $shop = $this->createShop();

        $this->clientRepository->method('findByShopAndPhone')
            ->willReturn(null);

        $this->em->expects(self::once())->method('persist');

        $result = $this->sut->resolveClient($shop, 'Nguyen', '0909876543');

        self::assertSame('Nguyen', $result->getFirstName());
        self::assertSame('', $result->getLastName());
    }

    #[Test]
    public function resolveClientStripsHtmlTags(): void
    {
        $shop = $this->createShop();

        $this->clientRepository->method('findByShopAndPhone')
            ->willReturn(null);

        $this->em->expects(self::once())->method('persist');

        $result = $this->sut->resolveClient($shop, '<script>alert("xss")</script>Nguyen Van', '0909876543');

        self::assertSame('alert("xss")Nguyen', $result->getFirstName());
        self::assertSame('Van', $result->getLastName());
    }

    #[Test]
    public function resolveClientNormalizesPhoneNumber(): void
    {
        $shop = $this->createShop();

        $this->clientRepository->method('findByShopAndPhone')
            ->with($shop, '+84901234567')
            ->willReturn(null);

        $this->em->expects(self::once())->method('persist');

        $result = $this->sut->resolveClient($shop, 'Test User', '+84 901 234 567');

        self::assertSame('+84901234567', $result->getPhone());
    }

    #[Test]
    public function resolveClientDoesNotUpdateNameWhenSame(): void
    {
        $shop = $this->createShop();
        $existingClient = new Client();
        $existingClient->setShop($shop);
        $existingClient->setFirstName('Nguyen');
        $existingClient->setLastName('Van A');
        $existingClient->setPhone('0901234567');

        $this->clientRepository->method('findByShopAndPhone')
            ->willReturn($existingClient);

        $result = $this->sut->resolveClient($shop, 'Nguyen Van A', '0901234567');

        self::assertSame('Nguyen', $result->getFirstName());
        self::assertSame('Van A', $result->getLastName());
    }

    private function createShop(): Shop
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName('Test Shop');
        $shop->setAddress('123 Test St');
        $shop->setPhone('0901234567');
        $shop->setSlug('test-shop');

        return $shop;
    }
}
