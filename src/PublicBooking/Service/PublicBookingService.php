<?php

declare(strict_types=1);

namespace App\PublicBooking\Service;

use App\Client\Util\PhoneNormalizer;
use App\Common\Exception\ApiException;
use App\Entity\Appointment;
use App\Entity\Shop;
use App\PublicBooking\Dto\BookingRequest;
use App\Repository\AppointmentRepository;
use App\Repository\ShopRepository;
use App\Repository\ShopServiceRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Enum\DayOfWeek;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

final class PublicBookingService
{
    private const TZ_NAME = 'Asia/Ho_Chi_Minh';
    private const MAX_BOOKING_DAYS_AHEAD = 30;
    private const MIN_ADVANCE_MINUTES = 60;

    public function __construct(
        private readonly ShopRepository $shopRepository,
        private readonly ShopServiceRepository $shopServiceRepository,
        private readonly WorkScheduleRepository $workScheduleRepository,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly SlotAvailabilityService $slotAvailabilityService,
        private readonly ClientResolverService $clientResolverService,
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getShopInfo(string $slug): array
    {
        $shop = $this->resolveShop($slug);

        $schedules = $this->workScheduleRepository->findByShop($shop);
        $services = $this->shopServiceRepository->findByShop($shop);

        $workingHours = [];
        foreach (DayOfWeek::cases() as $day) {
            $workingHours[$day->value] = null;
        }
        foreach ($schedules as $schedule) {
            if ($schedule->isOpen() && null !== $schedule->getOpenTime() && null !== $schedule->getCloseTime()) {
                $workingHours[$schedule->getDayOfWeek()->value] = [
                    'open' => $schedule->getOpenTime()->format('H:i'),
                    'close' => $schedule->getCloseTime()->format('H:i'),
                ];
            }
        }

        return [
            'name' => $shop->getName(),
            'address' => $shop->getAddress(),
            'phone' => $shop->getPhone(),
            'workingHours' => $workingHours,
            'services' => array_map(static fn ($s) => [
                'id' => (string) $s->getId(),
                'name' => $s->getName(),
                'duration' => $s->getDurationMinutes(),
                'price' => $s->getPrice(),
            ], $services),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAvailableSlots(string $slug, string $date, string $serviceId): array
    {
        $shop = $this->resolveShop($slug);
        $tz = new \DateTimeZone(self::TZ_NAME);

        $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $date, $tz);
        if (false === $dateObj) {
            throw new ApiException('VALIDATION_ERROR', 'Invalid date format. Use YYYY-MM-DD.', 400);
        }
        $dateObj = $dateObj->setTime(0, 0);

        $this->validateDate($dateObj);

        $service = $this->shopServiceRepository->findOneBy([
            'id' => Uuid::fromString($serviceId),
            'shop' => $shop,
        ]);
        if (null === $service || !$service->isActive()) {
            throw new ApiException('SERVICE_NOT_FOUND', 'Service not found.', 400);
        }

        $slots = $this->slotAvailabilityService->getSlots($shop, $dateObj, $service);

        return [
            'date' => $dateObj->format('Y-m-d'),
            'slots' => $slots,
        ];
    }

    public function book(string $slug, BookingRequest $dto): Appointment
    {
        $shop = $this->resolveShop($slug);
        $tz = new \DateTimeZone(self::TZ_NAME);

        $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $dto->date, $tz);
        if (false === $dateObj) {
            throw new ApiException('VALIDATION_ERROR', 'Invalid date format.', 400);
        }

        $timeParts = explode(':', $dto->time);
        $hour = (int) $timeParts[0];
        $minute = (int) $timeParts[1];
        $startTimeLocal = $dateObj->setTime($hour, $minute);

        $this->validateDate($startTimeLocal);
        $this->validateSlotAlignment($startTimeLocal);
        $this->validateMinAdvance($startTimeLocal);

        $service = $this->shopServiceRepository->findOneBy([
            'id' => Uuid::fromString($dto->serviceId),
            'shop' => $shop,
        ]);
        if (null === $service || !$service->isActive()) {
            throw new ApiException('SERVICE_NOT_FOUND', 'Service not found.', 400);
        }

        $this->validateWorkingHours($shop, $startTimeLocal, $service->getDurationMinutes());

        $startTimeUtc = $startTimeLocal->setTimezone(new \DateTimeZone('UTC'));
        $endTimeUtc = $startTimeUtc->modify("+{$service->getDurationMinutes()} minutes");

        $overlapping = $this->appointmentRepository->findNonCancelledInRange($shop, $startTimeUtc, $endTimeUtc);
        if ([] !== $overlapping) {
            throw new ApiException('SLOT_UNAVAILABLE', 'This time slot is no longer available.', 409);
        }

        $normalizedPhone = PhoneNormalizer::normalize($dto->clientPhone);
        $this->checkPhoneBookingRateLimit($shop, $normalizedPhone);

        $client = $this->clientResolverService->resolveClient($shop, $dto->clientName, $dto->clientPhone);

        $appointment = new Appointment();
        $appointment->setShop($shop);
        $appointment->setClient($client);
        $appointment->setService($service);
        $appointment->setPrice($service->getPrice());
        $appointment->setStartTime($startTimeUtc);
        $appointment->setEndTime($endTimeUtc);

        $this->em->persist($appointment);

        try {
            $this->em->flush();
        } catch (DriverException $e) {
            if ('23P01' === $e->getSQLState()) {
                throw new ApiException('SLOT_UNAVAILABLE', 'This time slot is no longer available.', 409);
            }
            throw $e;
        }

        return $appointment;
    }

    private function resolveShop(string $slug): Shop
    {
        $shop = $this->shopRepository->findBySlug($slug);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found.', 404);
        }

        return $shop;
    }

    private function validateDate(\DateTimeImmutable $date): void
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = $this->clock->now()->setTimezone($tz);
        $today = $now->setTime(0, 0);
        $dateOnly = $date->setTimezone($tz)->setTime(0, 0);

        if ($dateOnly < $today) {
            throw new ApiException('DATE_IN_PAST', 'Cannot book a date in the past.', 400);
        }

        $maxDate = $today->modify('+'.self::MAX_BOOKING_DAYS_AHEAD.' days');
        if ($dateOnly > $maxDate) {
            throw new ApiException('DATE_TOO_FAR_AHEAD', 'Cannot book more than 30 days in advance.', 400);
        }
    }

    private function validateSlotAlignment(\DateTimeImmutable $startTime): void
    {
        $minute = (int) $startTime->format('i');
        $second = (int) $startTime->format('s');

        if (0 !== $second || ($minute % 30) !== 0) {
            throw new ApiException('VALIDATION_ERROR', 'Time must be on a 30-minute boundary (e.g., 08:00, 08:30).', 400);
        }
    }

    private function validateMinAdvance(\DateTimeImmutable $startTimeLocal): void
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = $this->clock->now()->setTimezone($tz);
        $minAdvanceCutoff = $now->modify('+'.self::MIN_ADVANCE_MINUTES.' minutes');

        if ($startTimeLocal < $minAdvanceCutoff) {
            throw new ApiException('TOO_SHORT_NOTICE', 'Booking must be at least 1 hour in advance.', 400);
        }
    }

    private function validateWorkingHours(Shop $shop, \DateTimeImmutable $startTimeLocal, int $serviceDurationMinutes): void
    {
        $dayName = strtolower($startTimeLocal->format('l'));
        $dayOfWeek = DayOfWeek::from($dayName);

        $schedule = $this->workScheduleRepository->findByShopAndDay($shop, $dayOfWeek);
        if (null === $schedule || !$schedule->isOpen()) {
            throw new ApiException('OUTSIDE_WORKING_HOURS', 'The shop is closed on this day.', 400);
        }

        $openTime = $schedule->getOpenTime();
        $closeTime = $schedule->getCloseTime();
        if (null === $openTime || null === $closeTime) {
            throw new ApiException('OUTSIDE_WORKING_HOURS', 'The shop is closed on this day.', 400);
        }

        $dayOpen = $startTimeLocal->setTime((int) $openTime->format('H'), (int) $openTime->format('i'));
        $dayClose = $startTimeLocal->setTime((int) $closeTime->format('H'), (int) $closeTime->format('i'));
        $endTimeLocal = $startTimeLocal->modify("+{$serviceDurationMinutes} minutes");

        if ($startTimeLocal < $dayOpen || $endTimeLocal > $dayClose) {
            throw new ApiException('OUTSIDE_WORKING_HOURS', 'The selected time is outside shop working hours.', 400);
        }
    }

    private function checkPhoneBookingRateLimit(Shop $shop, string $phone): void
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = $this->clock->now()->setTimezone($tz);
        $todayStart = $now->setTime(0, 0)->setTimezone(new \DateTimeZone('UTC'));
        $todayEnd = $now->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $count = $this->appointmentRepository->countByShopPhoneAndDateRange($shop, $phone, $todayStart, $todayEnd);

        if ($count >= 5) {
            throw new ApiException('BOOKING_RATE_LIMIT_EXCEEDED', 'Maximum 5 bookings per phone number per day.', 429);
        }
    }
}
