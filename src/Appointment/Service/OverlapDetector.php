<?php

declare(strict_types=1);

namespace App\Appointment\Service;

use App\Entity\Shop;
use App\Repository\AppointmentRepository;
use Symfony\Component\Uid\Uuid;

final class OverlapDetector
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
    ) {
    }

    public function hasOverlap(
        Shop $shop,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?Uuid $excludeAppointmentId = null,
    ): bool {
        $overlapping = $this->appointmentRepository->findNonCancelledInRange(
            $shop,
            $startTime,
            $endTime,
            $excludeAppointmentId,
        );

        return $overlapping !== [];
    }
}
