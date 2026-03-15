<?php

declare(strict_types=1);

namespace App\Appointment\Service;

use App\Appointment\ValueObject\TimeSlot;
use App\Entity\Shop;
use App\Entity\WorkSchedule;
use App\Repository\AppointmentRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Enum\DayOfWeek;
use Psr\Clock\ClockInterface;

final class SlotCalculator
{
    private const SLOT_INTERVAL_MINUTES = 30;

    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly WorkScheduleRepository $workScheduleRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return TimeSlot[]
     */
    public function calculateAvailableSlots(
        Shop $shop,
        \DateTimeImmutable $date,
        int $serviceDurationMinutes,
    ): array {
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $dateInTz = $date->setTimezone($tz);
        $dayName = strtolower($dateInTz->format('l'));
        $dayOfWeek = DayOfWeek::from($dayName);

        $schedule = $this->getScheduleForDay($shop, $dayOfWeek);
        if ($schedule === null || !$schedule->isOpen()) {
            return [];
        }

        $openTime = $schedule->getOpenTime();
        $closeTime = $schedule->getCloseTime();
        if ($openTime === null || $closeTime === null) {
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

        $openHour = (int) $openTime->format('H');
        $openMinute = (int) $openTime->format('i');
        $closeHour = (int) $closeTime->format('H');
        $closeMinute = (int) $closeTime->format('i');

        $now = $this->clock->now()->setTimezone($tz);
        $slots = [];

        $slotStart = $dateInTz->setTime($openHour, $openMinute);
        $closingTime = $dateInTz->setTime($closeHour, $closeMinute);

        while (true) {
            $slotEnd = $slotStart->modify("+{$serviceDurationMinutes} minutes");

            if ($slotEnd > $closingTime) {
                break;
            }

            if ($slotStart >= $now && !$this->overlapsAny($slotStart, $slotEnd, $existingAppointments)) {
                $slots[] = new TimeSlot(
                    $slotStart->setTimezone(new \DateTimeZone('UTC')),
                    $slotEnd->setTimezone(new \DateTimeZone('UTC')),
                );
            }

            $slotStart = $slotStart->modify('+' . self::SLOT_INTERVAL_MINUTES . ' minutes');
        }

        return $slots;
    }

    private function getScheduleForDay(Shop $shop, DayOfWeek $day): ?WorkSchedule
    {
        return $this->workScheduleRepository->findByShopAndDay($shop, $day);
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
