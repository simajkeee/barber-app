<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Service;

use App\Client\Dto\ClientListFilter;
use App\Client\Dto\CreateClientRequest;
use App\Client\Dto\UpdateClientRequest;
use App\Client\Service\ClientService;
use App\Common\Exception\ApiException;
use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\User;
use App\Repository\ClientRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(ClientService::class)]
final class ClientServiceTest extends TestCase
{
    private ClientRepository&MockObject $clientRepository;
    private EntityManagerInterface&MockObject $em;
    private ClientService $sut;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->sut = new ClientService($this->clientRepository, $this->em);
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

    private function createClientEntity(Shop $shop, string $phone = '+84901234567'): Client
    {
        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName('John');
        $client->setLastName('Doe');
        $client->setPhone($phone);

        return $client;
    }

    // --- create ---

    #[Test]
    public function testCreateSuccessTrimsNamesAndNormalizesPhone(): void
    {
        $shop = $this->createShop();
        $dto = new CreateClientRequest(
            firstName: '  John  ',
            lastName: '  Doe  ',
            phone: '+84 901 234 567',
            email: 'john@example.com',
            notes: 'VIP client',
        );

        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $client = $this->sut->create($shop, $dto);

        self::assertSame('John', $client->getFirstName());
        self::assertSame('Doe', $client->getLastName());
        self::assertSame('+84901234567', $client->getPhone());
        self::assertSame('john@example.com', $client->getEmail());
        self::assertSame('VIP client', $client->getNotes());
        self::assertSame($shop, $client->getShop());
    }

    #[Test]
    public function testCreateWithNullOptionalFields(): void
    {
        $shop = $this->createShop();
        $dto = new CreateClientRequest(firstName: 'John', lastName: 'Doe', phone: '0901234567');

        $client = $this->sut->create($shop, $dto);

        self::assertNull($client->getEmail());
        self::assertNull($client->getNotes());
    }

    #[Test]
    public function testCreateThrows400ForInvalidPhoneFormat(): void
    {
        $shop = $this->createShop();
        $dto = new CreateClientRequest(firstName: 'John', lastName: 'Doe', phone: '123');

        $this->em->expects(self::never())->method('persist');

        try {
            $this->sut->create($shop, $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('VALIDATION_ERROR', $e->errorCode);
            self::assertSame('phone', $e->details[0]['field']);
        }
    }

    #[Test]
    public function testCreateThrows409OnDuplicatePhone(): void
    {
        $shop = $this->createShop();
        $dto = new CreateClientRequest(firstName: 'John', lastName: 'Doe', phone: '0901234567');

        $this->em->method('flush')->willThrowException(
            $this->createMock(UniqueConstraintViolationException::class),
        );

        try {
            $this->sut->create($shop, $dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('PHONE_ALREADY_EXISTS', $e->errorCode);
        }
    }

    #[Test]
    public function testCreateNormalizesPhoneWithDashesAndParentheses(): void
    {
        $shop = $this->createShop();
        $dto = new CreateClientRequest(firstName: 'John', lastName: 'Doe', phone: '(090) 123-4567');

        $client = $this->sut->create($shop, $dto);

        self::assertSame('0901234567', $client->getPhone());
    }

    #[Test]
    public function testCreateSamePhoneInDifferentShopSucceeds(): void
    {
        $shop1 = $this->createShop();
        $shop2 = $this->createShop();

        $dto = new CreateClientRequest(firstName: 'John', lastName: 'Doe', phone: '0901234567');

        $client1 = $this->sut->create($shop1, $dto);
        $client2 = $this->sut->create($shop2, $dto);

        self::assertSame('0901234567', $client1->getPhone());
        self::assertSame('0901234567', $client2->getPhone());
        self::assertNotSame($client1->getShop(), $client2->getShop());
    }

    // --- get ---

    #[Test]
    public function testGetReturnsClientWhenFound(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);
        $id = $client->getId();

        $this->clientRepository->method('findByShopAndId')->with($shop, $id)->willReturn($client);

        self::assertSame($client, $this->sut->get($shop, $id));
    }

    #[Test]
    public function testGetThrows404WhenClientNotFound(): void
    {
        $shop = $this->createShop();
        $id = Uuid::v7();

        $this->clientRepository->method('findByShopAndId')->willReturn(null);

        try {
            $this->sut->get($shop, $id);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(404, $e->statusCode);
            self::assertSame('CLIENT_NOT_FOUND', $e->errorCode);
        }
    }

    // --- update ---

    #[Test]
    public function testUpdateChangesOnlyProvidedStringFields(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);
        $client->setEmail('old@example.com');
        $client->setNotes('Old notes');

        $this->clientRepository->method('findByShopAndId')->willReturn($client);

        $dto = new UpdateClientRequest(firstName: '  Jane  ');
        $result = $this->sut->update($shop, $client->getId(), $dto, ['firstName']);

        self::assertSame('Jane', $result->getFirstName());
        self::assertSame('Doe', $result->getLastName());
        self::assertSame('+84901234567', $result->getPhone());
        self::assertSame('old@example.com', $result->getEmail());
        self::assertSame('Old notes', $result->getNotes());
    }

    #[Test]
    public function testUpdateTrimsLastName(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);

        $dto = new UpdateClientRequest(lastName: '  Smith  ');
        $result = $this->sut->update($shop, $client->getId(), $dto, ['lastName']);

        self::assertSame('Smith', $result->getLastName());
    }

    #[Test]
    public function testUpdateThrows404WhenClientNotFound(): void
    {
        $shop = $this->createShop();

        $this->clientRepository->method('findByShopAndId')->willReturn(null);
        $this->em->expects(self::never())->method('flush');

        $dto = new UpdateClientRequest(firstName: 'Jane');

        try {
            $this->sut->update($shop, Uuid::v7(), $dto, ['firstName']);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(404, $e->statusCode);
            self::assertSame('CLIENT_NOT_FOUND', $e->errorCode);
        }
    }

    #[Test]
    public function testUpdateClearsEmailWhenExplicitlyNull(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);
        $client->setEmail('old@example.com');

        $this->clientRepository->method('findByShopAndId')->willReturn($client);

        $dto = new UpdateClientRequest(email: null);
        $result = $this->sut->update($shop, $client->getId(), $dto, ['email']);

        self::assertNull($result->getEmail());
    }

    #[Test]
    public function testUpdateClearsNotesWhenExplicitlyNull(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);
        $client->setNotes('Some notes');

        $this->clientRepository->method('findByShopAndId')->willReturn($client);

        $dto = new UpdateClientRequest(notes: null);
        $result = $this->sut->update($shop, $client->getId(), $dto, ['notes']);

        self::assertNull($result->getNotes());
    }

    #[Test]
    public function testUpdatePreservesEmailWhenNotInProvidedFields(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);
        $client->setEmail('keep@example.com');

        $this->clientRepository->method('findByShopAndId')->willReturn($client);

        $dto = new UpdateClientRequest(firstName: 'Jane');
        $result = $this->sut->update($shop, $client->getId(), $dto, ['firstName']);

        self::assertSame('keep@example.com', $result->getEmail());
    }

    #[Test]
    public function testUpdatePhoneNormalizesAndValidates(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->clientRepository->method('findByShopAndPhone')->willReturn(null);

        $dto = new UpdateClientRequest(phone: '+1 (555) 123-4567');
        $result = $this->sut->update($shop, $client->getId(), $dto, ['phone']);

        self::assertSame('+15551234567', $result->getPhone());
    }

    #[Test]
    public function testUpdatePhoneThrows400ForInvalidFormat(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);

        $dto = new UpdateClientRequest(phone: '12');

        try {
            $this->sut->update($shop, $client->getId(), $dto, ['phone']);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('VALIDATION_ERROR', $e->errorCode);
        }
    }

    #[Test]
    public function testUpdatePhoneThrows409WhenTakenByAnotherClient(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);
        $otherClient = $this->createClientEntity($shop, '+84999888777');

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->clientRepository->method('findByShopAndPhone')->willReturn($otherClient);

        $dto = new UpdateClientRequest(phone: '+84999888777');

        try {
            $this->sut->update($shop, $client->getId(), $dto, ['phone']);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('PHONE_ALREADY_EXISTS', $e->errorCode);
        }
    }

    #[Test]
    public function testUpdatePhoneAllowsSamePhoneForSameClient(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop, '+84901234567');

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->clientRepository->method('findByShopAndPhone')->willReturn($client);

        $dto = new UpdateClientRequest(phone: '+84901234567');
        $result = $this->sut->update($shop, $client->getId(), $dto, ['phone']);

        self::assertSame('+84901234567', $result->getPhone());
    }

    #[Test]
    public function testUpdateCatchesUniqueConstraintViolation(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);

        $this->em->method('flush')->willThrowException(
            $this->createMock(UniqueConstraintViolationException::class),
        );

        $dto = new UpdateClientRequest(firstName: 'Jane');

        try {
            $this->sut->update($shop, $client->getId(), $dto, ['firstName']);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('PHONE_ALREADY_EXISTS', $e->errorCode);
        }
    }

    // --- delete ---

    #[Test]
    public function testDeleteRemovesAndFlushes(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);

        $this->clientRepository->method('findByShopAndId')->willReturn($client);
        $this->em->expects(self::once())->method('remove')->with($client);
        $this->em->expects(self::once())->method('flush');

        $this->sut->delete($shop, $client->getId());
    }

    #[Test]
    public function testDeleteThrows404WhenClientNotFound(): void
    {
        $shop = $this->createShop();

        $this->clientRepository->method('findByShopAndId')->willReturn(null);
        $this->em->expects(self::never())->method('remove');

        try {
            $this->sut->delete($shop, Uuid::v7());
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(404, $e->statusCode);
            self::assertSame('CLIENT_NOT_FOUND', $e->errorCode);
        }
    }

    // --- recordVisit ---

    #[Test]
    public function testRecordVisitIncrementsCountAndSetsLastVisitAt(): void
    {
        $client = $this->createClientEntity($this->createShop());
        $visitDate = new \DateTimeImmutable('2026-03-13 10:00:00');

        self::assertSame(0, $client->getVisitCount());
        self::assertNull($client->getLastVisitAt());

        $this->em->expects(self::once())->method('flush');

        $this->sut->recordVisit($client, $visitDate);

        self::assertSame(1, $client->getVisitCount());
        self::assertSame($visitDate, $client->getLastVisitAt());
    }

    #[Test]
    public function testRecordVisitIncrementsCountMultipleTimes(): void
    {
        $client = $this->createClientEntity($this->createShop());

        $this->sut->recordVisit($client, new \DateTimeImmutable('2026-03-10'));
        $this->sut->recordVisit($client, new \DateTimeImmutable('2026-03-12'));

        self::assertSame(2, $client->getVisitCount());
    }

    #[Test]
    public function testRecordVisitDoesNotOverwriteNewerLastVisitAt(): void
    {
        $client = $this->createClientEntity($this->createShop());
        $newerDate = new \DateTimeImmutable('2026-03-15');
        $olderDate = new \DateTimeImmutable('2026-03-10');

        $this->sut->recordVisit($client, $newerDate);
        $this->sut->recordVisit($client, $olderDate);

        self::assertSame(2, $client->getVisitCount());
        self::assertSame($newerDate, $client->getLastVisitAt());
    }

    #[Test]
    public function testRecordVisitDoesNotOverwriteEqualLastVisitAt(): void
    {
        $client = $this->createClientEntity($this->createShop());
        $date = new \DateTimeImmutable('2026-03-15 10:00:00');
        $sameDate = new \DateTimeImmutable('2026-03-15 10:00:00');

        $this->sut->recordVisit($client, $date);
        $this->sut->recordVisit($client, $sameDate);

        self::assertSame(2, $client->getVisitCount());
        self::assertSame($date, $client->getLastVisitAt());
    }

    // --- list ---

    #[Test]
    public function testListReturnsSerializedDataWithPagination(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);

        $this->clientRepository->method('findByShopWithFilter')->willReturn([
            'clients' => [$client],
            'nextCursor' => 'abc123',
            'hasMore' => true,
        ]);

        $result = $this->sut->list($shop, new ClientListFilter());

        self::assertCount(1, $result['data']);
        self::assertSame((string) $client->getId(), $result['data'][0]['id']);
        self::assertSame('abc123', $result['pagination']['nextCursor']);
        self::assertTrue($result['pagination']['hasMore']);
    }

    #[Test]
    public function testListReturnsEmptyDataWhenNoClients(): void
    {
        $shop = $this->createShop();

        $this->clientRepository->method('findByShopWithFilter')->willReturn([
            'clients' => [],
            'nextCursor' => null,
            'hasMore' => false,
        ]);

        $result = $this->sut->list($shop, new ClientListFilter());

        self::assertSame([], $result['data']);
        self::assertNull($result['pagination']['nextCursor']);
        self::assertFalse($result['pagination']['hasMore']);
    }

    // --- serializeClient ---

    #[Test]
    public function testSerializeClientReturnsExpectedStructure(): void
    {
        $shop = $this->createShop();
        $client = $this->createClientEntity($shop);
        $client->setEmail('test@example.com');
        $client->setNotes('A note');

        $result = ClientService::serializeClient($client);

        self::assertSame((string) $client->getId(), $result['id']);
        self::assertSame('John', $result['firstName']);
        self::assertSame('Doe', $result['lastName']);
        self::assertSame('+84901234567', $result['phone']);
        self::assertSame('test@example.com', $result['email']);
        self::assertSame('A note', $result['notes']);
        self::assertNull($result['lastVisitAt']);
        self::assertSame(0, $result['visitCount']);
        self::assertIsString($result['createdAt']);
        self::assertIsString($result['updatedAt']);
    }

    #[Test]
    public function testSerializeClientWithNullOptionalFields(): void
    {
        $client = $this->createClientEntity($this->createShop());

        $result = ClientService::serializeClient($client);

        self::assertNull($result['email']);
        self::assertNull($result['notes']);
        self::assertNull($result['lastVisitAt']);
    }

    #[Test]
    public function testSerializeClientFormatsLastVisitAtAsAtom(): void
    {
        $client = $this->createClientEntity($this->createShop());
        $visitDate = new \DateTimeImmutable('2026-03-13T10:30:00+07:00');
        $client->setLastVisitAt($visitDate);

        $result = ClientService::serializeClient($client);

        self::assertSame($visitDate->format(\DateTimeInterface::ATOM), $result['lastVisitAt']);
    }
}
