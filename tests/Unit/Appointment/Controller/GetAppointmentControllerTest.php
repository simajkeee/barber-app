<?php

declare(strict_types=1);

namespace App\Tests\Unit\Appointment\Controller;

use App\Appointment\Controller\GetAppointmentController;
use App\Appointment\Enum\AppointmentStatus;
use App\Common\Exception\ApiException;
use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\ShopService;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Shop\Service\ShopManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(GetAppointmentController::class)]
final class GetAppointmentControllerTest extends TestCase
{
    private ShopManager&MockObject $shopManager;
    private AppointmentRepository&MockObject $appointmentRepository;
    private GetAppointmentController $sut;

    protected function setUp(): void
    {
        $this->shopManager = $this->createMock(ShopManager::class);
        $this->appointmentRepository = $this->createMock(AppointmentRepository::class);
        $this->sut = new GetAppointmentController($this->shopManager, $this->appointmentRepository);
    }

    #[Test]
    public function itReturnsAppointment(): void
    {
        $user = new User();
        $shop = $this->createShop($user);
        $appointment = $this->createAppointment($shop);
        $id = (string) $appointment->getId();

        $this->shopManager->method('getShopForUser')->with($user)->willReturn($shop);
        $this->appointmentRepository->method('findByShopAndId')->willReturn($appointment);

        $response = ($this->sut)($user, $id);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertSame($id, $body['id']);
    }

    #[Test]
    public function itThrows404WhenShopNotFound(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Shop not found. Create one first.');

        $this->shopManager->method('getShopForUser')->willReturn(null);

        ($this->sut)(new User(), Uuid::v7()->toRfc4122());
    }

    #[Test]
    public function itThrows404WhenIdIsNotValidUuid(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Appointment not found.');

        $user = new User();
        $this->shopManager->method('getShopForUser')->willReturn($this->createShop($user));

        ($this->sut)($user, 'not-a-uuid');
    }

    #[Test]
    public function itThrows404WhenAppointmentNotFound(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Appointment not found.');

        $user = new User();
        $this->shopManager->method('getShopForUser')->willReturn($this->createShop($user));
        $this->appointmentRepository->method('findByShopAndId')->willReturn(null);

        ($this->sut)($user, Uuid::v7()->toRfc4122());
    }

    #[Test]
    public function itDoesNotReturnAppointmentFromAnotherShop(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Appointment not found.');

        $user = new User();
        $userShop = $this->createShop($user);

        $this->shopManager->method('getShopForUser')->willReturn($userShop);
        // Repository scopes by shop — returns null when shop doesn't match
        $this->appointmentRepository->method('findByShopAndId')->willReturn(null);

        ($this->sut)($user, Uuid::v7()->toRfc4122());
    }

    private function createShop(User $user): Shop
    {
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

    private function createAppointment(Shop $shop): Appointment
    {
        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName('John');
        $client->setLastName('Doe');
        $client->setPhone('0901234567');

        $service = new ShopService();
        $service->setShop($shop);
        $service->setName('Haircut');
        $service->setDurationMinutes(30);
        $service->setPrice(150000);
        $service->setIsActive(true);

        $appointment = new Appointment();
        $appointment->setShop($shop);
        $appointment->setClient($client);
        $appointment->setService($service);
        $appointment->setPrice($service->getPrice());
        $appointment->setStartTime(new \DateTimeImmutable('2026-03-16 02:00:00', new \DateTimeZone('UTC')));
        $appointment->setEndTime(new \DateTimeImmutable('2026-03-16 02:30:00', new \DateTimeZone('UTC')));
        $appointment->setStatus(AppointmentStatus::SCHEDULED);

        return $appointment;
    }
}