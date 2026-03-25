<?php

declare(strict_types=1);

namespace App\Client\EventListener;

use App\Appointment\Event\AppointmentCompleted;
use App\Client\Service\ClientService;
use App\Repository\AppointmentRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class UpdateClientStatsOnAppointmentCompleted
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private ClientService $clientService,
    ) {
    }

    public function __invoke(AppointmentCompleted $event): void
    {
        $appointment = $this->appointmentRepository->find($event->appointmentId);
        if (null === $appointment) {
            return;
        }

        $this->clientService->recordVisit($appointment->getClient(), $appointment->getStartTime());
    }
}
