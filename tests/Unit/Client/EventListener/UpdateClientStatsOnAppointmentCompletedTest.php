<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\EventListener;

use App\Appointment\Event\AppointmentCompleted;
use App\Client\EventListener\UpdateClientStatsOnAppointmentCompleted;
use App\Client\Service\ClientService;
use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\Shop;
use App\Entity\ShopService;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(UpdateClientStatsOnAppointmentCompleted::class)]
final class UpdateClientStatsOnAppointmentCompletedTest extends TestCase
{
    private AppointmentRepository&MockObject $appointmentRepository;
    private ClientService&MockObject $clientService;
    private UpdateClientStatsOnAppointmentCompleted $sut;

    protected function setUp(): void
    {
        $this->appointmentRepository = $this->createMock(AppointmentRepository::class);
        $this->clientService = $this->createMock(ClientService::class);
        $this->sut = new UpdateClientStatsOnAppointmentCompleted(
            $this->appointmentRepository,
            $this->clientService,
        );
    }

    private function createAppointment(): Appointment
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

        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName('John');
        $client->setLastName('Doe');
        $client->setPhone('0901234567');

        $service = new ShopService();
        $service->setShop($shop);
        $service->setName('Haircut');
        $service->setDurationMinutes(30);
        $service->setPrice(100000);

        $appointment = new Appointment();
        $appointment->setShop($shop);
        $appointment->setClient($client);
        $appointment->setService($service);
        $appointment->setPrice(100000);
        $startTime = new \DateTimeImmutable('2026-03-16 09:00:00', new \DateTimeZone('UTC'));
        $appointment->setStartTime($startTime);
        $appointment->setEndTime($startTime->modify('+30 minutes'));

        return $appointment;
    }

    #[Test]
    public function testRecordVisitCalledWithCorrectArguments(): void
    {
        $appointment = $this->createAppointment();
        $event = new AppointmentCompleted($appointment->getId());

        $this->appointmentRepository->method('find')
            ->with($appointment->getId())
            ->willReturn($appointment);

        $this->clientService->expects(self::once())
            ->method('recordVisit')
            ->with($appointment->getClient(), $appointment->getStartTime());

        ($this->sut)($event);
    }

    #[Test]
    public function testDoesNothingWhenAppointmentNotFound(): void
    {
        $event = new AppointmentCompleted(Uuid::v7());

        $this->appointmentRepository->method('find')->willReturn(null);
        $this->clientService->expects(self::never())->method('recordVisit');

        ($this->sut)($event);
    }
}
