<?php

declare(strict_types=1);

namespace App\Appointment\Service;

use App\Appointment\Dto\CreateAppointmentRequest;
use App\Appointment\Dto\UpdateAppointmentRequest;
use App\Appointment\Enum\AppointmentStatus;
use App\Appointment\Event\AppointmentCompleted;
use App\Common\Exception\ApiException;
use App\Entity\Appointment;
use App\Entity\Shop;
use App\Entity\ShopService;
use App\Entity\WorkSchedule;
use App\Repository\AppointmentRepository;
use App\Repository\ClientRepository;
use App\Repository\ShopServiceRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Enum\DayOfWeek;
use App\Subscription\Service\AppointmentLimitChecker;
use App\Subscription\Service\SubscriptionService;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AppointmentService
{
    private const TZ_NAME = 'Asia/Ho_Chi_Minh';

    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly ClientRepository $clientRepository,
        private readonly ShopServiceRepository $shopServiceRepository,
        private readonly WorkScheduleRepository $workScheduleRepository,
        private readonly OverlapDetector $overlapDetector,
        private readonly SlotCalculator $slotCalculator,
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AppointmentLimitChecker $appointmentLimitChecker,
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function create(CreateAppointmentRequest $dto, Shop $shop): Appointment
    {
        $this->appointmentLimitChecker->check($shop);

        $client = $this->clientRepository->findByShopAndId($shop, Uuid::fromString($dto->clientId));
        if ($client === null) {
            throw new ApiException('CLIENT_NOT_FOUND', 'Client not found.', 404);
        }

        $service = $this->resolveActiveService($shop, Uuid::fromString($dto->serviceId));

        $startTime = $this->parseStartTime($dto->startTime);
        $this->validateSlotAlignment($startTime);
        $this->validateNotInPast($startTime);

        $endTime = $startTime->modify("+{$service->getDurationMinutes()} minutes");

        $this->validateWorkingHours($shop, $startTime, $endTime);

        if ($this->overlapDetector->hasOverlap($shop, $startTime, $endTime)) {
            throw new ApiException('APPOINTMENT_OVERLAP', 'This time slot conflicts with an existing appointment.', 409);
        }

        $appointment = new Appointment();
        $appointment->setShop($shop);
        $appointment->setClient($client);
        $appointment->setService($service);
        $appointment->setPrice($service->getPrice());
        $appointment->setStartTime($startTime);
        $appointment->setEndTime($endTime);
        $appointment->setNotes($dto->notes);

        $this->em->persist($appointment);

        try {
            $this->em->flush();
        } catch (DriverException $e) {
            if ($e->getSQLState() === '23P01') {
                throw new ApiException('APPOINTMENT_OVERLAP', 'This time slot conflicts with an existing appointment.', 409);
            }
            throw $e;
        }

        $this->subscriptionService->incrementAppointmentCount($shop);

        return $appointment;
    }

    /**
     * @param string[] $providedFields
     */
    public function update(Appointment $appointment, UpdateAppointmentRequest $dto, array $providedFields): Appointment
    {
        $this->assertNotTerminal($appointment);

        $shop = $appointment->getShop();
        $service = $appointment->getService();
        $startTime = $appointment->getStartTime();
        $serviceChanged = false;

        if ($dto->clientId !== null) {
            $client = $this->clientRepository->findByShopAndId($shop, Uuid::fromString($dto->clientId));
            if ($client === null) {
                throw new ApiException('CLIENT_NOT_FOUND', 'Client not found.', 404);
            }
            $appointment->setClient($client);
        }

        if ($dto->serviceId !== null) {
            $service = $this->resolveActiveService($shop, Uuid::fromString($dto->serviceId));
            $appointment->setService($service);
            $appointment->setPrice($service->getPrice());
            $serviceChanged = true;
        }

        if ($dto->startTime !== null) {
            $startTime = $this->parseStartTime($dto->startTime);
            $this->validateSlotAlignment($startTime);
            $this->validateNotInPast($startTime);
            $appointment->setStartTime($startTime);
        }

        if ($dto->startTime !== null || $serviceChanged) {
            $endTime = $startTime->modify("+{$service->getDurationMinutes()} minutes");
            $appointment->setEndTime($endTime);
        }

        $effectiveEnd = $appointment->getEndTime();
        $effectiveStart = $appointment->getStartTime();

        if ($dto->startTime !== null || $serviceChanged) {
            $this->validateWorkingHours($shop, $effectiveStart, $effectiveEnd);

            if ($this->overlapDetector->hasOverlap($shop, $effectiveStart, $effectiveEnd, $appointment->getId())) {
                throw new ApiException('APPOINTMENT_OVERLAP', 'This time slot conflicts with an existing appointment.', 409);
            }
        }

        if (in_array('notes', $providedFields, true)) {
            $appointment->setNotes($dto->notes);
        }

        try {
            $this->em->flush();
        } catch (DriverException $e) {
            if ($e->getSQLState() === '23P01') {
                throw new ApiException('APPOINTMENT_OVERLAP', 'This time slot conflicts with an existing appointment.', 409);
            }
            throw $e;
        }

        return $appointment;
    }

    public function changeStatus(Appointment $appointment, AppointmentStatus $newStatus): Appointment
    {
        $currentStatus = $appointment->getStatus();

        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new ApiException(
                'INVALID_STATUS_TRANSITION',
                "Cannot change status from {$currentStatus->value} to {$newStatus->value}.",
                403,
            );
        }

        $appointment->setStatus($newStatus);
        $this->em->flush();

        if ($newStatus === AppointmentStatus::COMPLETED) {
            $this->eventDispatcher->dispatch(new AppointmentCompleted($appointment->getId()));
        }

        if ($newStatus === AppointmentStatus::CANCELLED) {
            $this->subscriptionService->decrementAppointmentCount($appointment->getShop());
        }

        return $appointment;
    }

    public function cancel(Appointment $appointment): Appointment
    {
        $this->assertNotTerminal($appointment);

        return $this->changeStatus($appointment, AppointmentStatus::CANCELLED);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDailySchedule(Shop $shop, \DateTimeImmutable $date): array
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $dateInTz = $date->setTimezone($tz);
        $dayName = strtolower($dateInTz->format('l'));
        $dayOfWeek = DayOfWeek::from($dayName);

        $schedule = $this->getScheduleForDay($shop, $dayOfWeek);

        $dayStart = $dateInTz->setTime(0, 0);
        $dayEnd = $dayStart->modify('+1 day');
        $dayStartUtc = $dayStart->setTimezone(new \DateTimeZone('UTC'));
        $dayEndUtc = $dayEnd->setTimezone(new \DateTimeZone('UTC'));

        $appointments = $this->appointmentRepository->findByShopAndDate($shop, $dayStartUtc, $dayEndUtc);

        $workingHours = null;
        if ($schedule !== null && $schedule->isOpen()) {
            $workingHours = [
                'openTime' => $schedule->getOpenTime()?->format('H:i'),
                'closeTime' => $schedule->getCloseTime()?->format('H:i'),
            ];
        }

        return [
            'date' => $dateInTz->format('Y-m-d'),
            'workingHours' => $workingHours,
            'appointments' => array_map(self::serializeAppointment(...), $appointments),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAvailableSlots(Shop $shop, \DateTimeImmutable $date, ShopService $service): array
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $dateInTz = $date->setTimezone($tz);
        $dayName = strtolower($dateInTz->format('l'));
        $dayOfWeek = DayOfWeek::from($dayName);

        $schedule = $this->getScheduleForDay($shop, $dayOfWeek);
        if ($schedule === null || !$schedule->isOpen()) {
            throw new ApiException('SHOP_CLOSED', 'The shop is closed on this day.', 400);
        }

        $slots = $this->slotCalculator->calculateAvailableSlots($shop, $date, $service->getDurationMinutes());

        return [
            'date' => $dateInTz->format('Y-m-d'),
            'serviceDurationMinutes' => $service->getDurationMinutes(),
            'slots' => array_map(static fn ($slot) => [
                'startTime' => $slot->startTime->setTimezone($tz)->format(\DateTimeInterface::ATOM),
                'endTime' => $slot->endTime->setTimezone($tz)->format(\DateTimeInterface::ATOM),
            ], $slots),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRevenue(Shop $shop, \DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array
    {
        $diff = $dateTo->diff($dateFrom);
        if ($diff->days > 90) {
            throw new ApiException('VALIDATION_ERROR', 'Date range cannot exceed 90 days.', 400);
        }

        $tz = new \DateTimeZone(self::TZ_NAME);
        $fromInTz = $dateFrom->setTimezone($tz)->setTime(0, 0);
        $toInTz = $dateTo->setTimezone($tz)->setTime(0, 0);

        $fromUtc = $fromInTz->setTimezone(new \DateTimeZone('UTC'));
        $toUtc = $toInTz->setTimezone(new \DateTimeZone('UTC'));

        $dailyBreakdown = $this->appointmentRepository->getRevenueByDay($shop, $fromUtc, $toUtc);

        $totalRevenue = 0;
        $totalCount = 0;
        foreach ($dailyBreakdown as $day) {
            $totalRevenue += $day['revenue'];
            $totalCount += $day['count'];
        }

        return [
            'dateFrom' => $fromInTz->format('Y-m-d'),
            'dateTo' => $toInTz->format('Y-m-d'),
            'totalRevenue' => $totalRevenue,
            'appointmentCount' => $totalCount,
            'dailyBreakdown' => $dailyBreakdown,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeAppointment(Appointment $appointment): array
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $client = $appointment->getClient();
        $service = $appointment->getService();

        return [
            'id' => (string) $appointment->getId(),
            'client' => [
                'id' => (string) $client->getId(),
                'firstName' => $client->getFirstName(),
                'lastName' => $client->getLastName(),
                'phone' => $client->getPhone(),
            ],
            'service' => [
                'id' => (string) $service->getId(),
                'name' => $service->getName(),
                'durationMinutes' => $service->getDurationMinutes(),
                'price' => $appointment->getPrice(),
            ],
            'startTime' => $appointment->getStartTime()->setTimezone($tz)->format(\DateTimeInterface::ATOM),
            'endTime' => $appointment->getEndTime()->setTimezone($tz)->format(\DateTimeInterface::ATOM),
            'status' => $appointment->getStatus()->value,
            'notes' => $appointment->getNotes(),
            'createdAt' => $appointment->getCreatedAt()->setTimezone($tz)->format(\DateTimeInterface::ATOM),
            'updatedAt' => $appointment->getUpdatedAt()->setTimezone($tz)->format(\DateTimeInterface::ATOM),
        ];
    }

    private function resolveActiveService(Shop $shop, Uuid $serviceId): ShopService
    {
        $service = $this->shopServiceRepository->findOneBy(['id' => $serviceId, 'shop' => $shop]);
        if ($service === null) {
            throw new ApiException('SERVICE_NOT_FOUND', 'Service not found.', 404);
        }

        if (!$service->isActive()) {
            throw new ApiException('SERVICE_INACTIVE', 'Cannot book an inactive service.', 400);
        }

        return $service;
    }

    private function parseStartTime(string $value): \DateTimeImmutable
    {
        try {
            $dt = new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new ApiException('VALIDATION_ERROR', 'Invalid start time format.', 400);
        }

        return $dt->setTimezone(new \DateTimeZone('UTC'));
    }

    private function validateSlotAlignment(\DateTimeImmutable $startTime): void
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $inTz = $startTime->setTimezone($tz);
        $minute = (int) $inTz->format('i');
        $second = (int) $inTz->format('s');

        if ($second !== 0 || ($minute % 30) !== 0) {
            throw new ApiException('INVALID_SLOT_ALIGNMENT', 'Start time must be on a 30-minute boundary.', 400);
        }
    }

    private function validateNotInPast(\DateTimeImmutable $startTime): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($startTime <= $now) {
            throw new ApiException('TIME_IN_PAST', 'Cannot book an appointment in the past.', 422);
        }
    }

    private function validateWorkingHours(Shop $shop, \DateTimeImmutable $startTime, \DateTimeImmutable $endTime): void
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $startInTz = $startTime->setTimezone($tz);
        $endInTz = $endTime->setTimezone($tz);
        $dayName = strtolower($startInTz->format('l'));
        $dayOfWeek = DayOfWeek::from($dayName);

        $schedule = $this->getScheduleForDay($shop, $dayOfWeek);
        if ($schedule === null || !$schedule->isOpen()) {
            throw new ApiException('SHOP_CLOSED', 'The shop is closed on this day.', 422);
        }

        $openTime = $schedule->getOpenTime();
        $closeTime = $schedule->getCloseTime();
        if ($openTime === null || $closeTime === null) {
            throw new ApiException('SHOP_CLOSED', 'The shop is closed on this day.', 422);
        }

        $openHour = (int) $openTime->format('H');
        $openMinute = (int) $openTime->format('i');
        $closeHour = (int) $closeTime->format('H');
        $closeMinute = (int) $closeTime->format('i');

        $dayOpen = $startInTz->setTime($openHour, $openMinute);
        $dayClose = $startInTz->setTime($closeHour, $closeMinute);

        if ($startInTz < $dayOpen || $endInTz > $dayClose) {
            throw new ApiException('OUTSIDE_WORKING_HOURS', 'The selected time is outside shop working hours.', 422);
        }
    }

    private function getScheduleForDay(Shop $shop, DayOfWeek $day): ?WorkSchedule
    {
        return $this->workScheduleRepository->findByShopAndDay($shop, $day);
    }

    private function assertNotTerminal(Appointment $appointment): void
    {
        if ($appointment->getStatus()->isTerminal()) {
            throw new ApiException(
                'APPOINTMENT_NOT_MODIFIABLE',
                "Cannot modify a {$appointment->getStatus()->value} appointment.",
                403,
            );
        }
    }
}
