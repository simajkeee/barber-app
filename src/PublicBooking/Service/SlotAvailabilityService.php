<?php

declare(strict_types=1);

namespace App\PublicBooking\Service;

use App\Entity\Shop;
use App\Entity\ShopService;
use App\Repository\AppointmentRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Enum\DayOfWeek;
use Psr\Clock\ClockInterface;

final class SlotAvailabilityService
{
    private const TZ_NAME = 'Asia/Ho_Chi_Minh';
    private const SLOT_INTERVAL_MINUTES = 30;
    private const MIN_ADVANCE_MINUTES = 60;

    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly WorkScheduleRepository $workScheduleRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return array<array{time: string, available: bool}>
     */
    public function getSlots(Shop $shop, \DateTimeImmutable $date, ShopService $service): array
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $dateInTz = $date->setTimezone($tz);
        $dayName = strtolower($dateInTz->format('l'));
        $dayOfWeek = DayOfWeek::from($dayName);

        $schedule = $this->workScheduleRepository->findByShopAndDay($shop, $dayOfWeek);
        if (null === $schedule || !$schedule->isOpen()) {
            return [];
        }

        $openTime = $schedule->getOpenTime();
        $closeTime = $schedule->getCloseTime();
        if (null === $openTime || null === $closeTime) {
            return [];
        }

        $dayStart = $dateInTz->setTime(0, 0);
        $dayEnd = $dayStart->modify('+1 day');
        $dayStartUtc = $dayStart->setTimezone(new \DateTimeZone('UTC'));
        $dayEndUtc = $dayEnd->setTimezone(new \DateTimeZone('UTC'));

        $existingAppointments = $this->appointmentRepository->findNonCancelledInRange(
            $shop,
            $dayStartUtc,
            $dayEndUtc,
        );

        $now = $this->clock->now()->setTimezone($tz);
        $minAdvanceCutoff = $now->modify('+'.self::MIN_ADVANCE_MINUTES.' minutes');
        $serviceDuration = $service->getDurationMinutes();

        $slotStart = $dateInTz->setTime((int) $openTime->format('H'), (int) $openTime->format('i'));
        $closingTime = $dateInTz->setTime((int) $closeTime->format('H'), (int) $closeTime->format('i'));

        $slots = [];
        while (true) {
            $slotEnd = $slotStart->modify("+{$serviceDuration} minutes");

            if ($slotEnd > $closingTime) {
                break;
            }

            $isPast = $slotStart < $now;
            $isTooSoon = $slotStart < $minAdvanceCutoff;
            $hasOverlap = $this->overlapsAny($slotStart, $slotEnd, $existingAppointments);

            $slots[] = [
                'time' => $slotStart->format('H:i'),
                'available' => !$isPast && !$isTooSoon && !$hasOverlap,
            ];

            $slotStart = $slotStart->modify('+'.self::SLOT_INTERVAL_MINUTES.' minutes');
        }

        return $slots;
    }

    public function isSlotAvailable(Shop $shop, \DateTimeImmutable $dateTime, ShopService $service): bool
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $dateTimeInTz = $dateTime->setTimezone($tz);
        $dayName = strtolower($dateTimeInTz->format('l'));
        $dayOfWeek = DayOfWeek::from($dayName);

        $schedule = $this->workScheduleRepository->findByShopAndDay($shop, $dayOfWeek);
        if (null === $schedule || !$schedule->isOpen()) {
            return false;
        }

        $openTime = $schedule->getOpenTime();
        $closeTime = $schedule->getCloseTime();
        if (null === $openTime || null === $closeTime) {
            return false;
        }

        $dayOpen = $dateTimeInTz->setTime((int) $openTime->format('H'), (int) $openTime->format('i'));
        $dayClose = $dateTimeInTz->setTime((int) $closeTime->format('H'), (int) $closeTime->format('i'));

        $endTime = $dateTimeInTz->modify("+{$service->getDurationMinutes()} minutes");

        if ($dateTimeInTz < $dayOpen || $endTime > $dayClose) {
            return false;
        }

        $startTimeUtc = $dateTime->setTimezone(new \DateTimeZone('UTC'));
        $endTimeCalcUtc = $endTime->setTimezone(new \DateTimeZone('UTC'));

        $overlapping = $this->appointmentRepository->findNonCancelledInRange(
            $shop,
            $startTimeUtc,
            $endTimeCalcUtc,
        );

        return [] === $overlapping;
    }

    /**
     * @param \App\Entity\Appointment[] $appointments
     */
    private function overlapsAny(
        \DateTimeImmutable $slotStart,
        \DateTimeImmutable $slotEnd,
        array $appointments,
    ): bool {
        $slotStartUtc = $slotStart->setTimezone(new \DateTimeZone('UTC'));
        $slotEndUtc = $slotEnd->setTimezone(new \DateTimeZone('UTC'));

        foreach ($appointments as $appointment) {
            if ($slotStartUtc < $appointment->getEndTime() && $slotEndUtc > $appointment->getStartTime()) {
                return true;
            }
        }

        return false;
    }
}
