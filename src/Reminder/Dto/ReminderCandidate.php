<?php

declare(strict_types=1);

namespace App\Reminder\Dto;

use Symfony\Component\Uid\Uuid;

final readonly class ReminderCandidate
{
    public function __construct(
        public Uuid $clientId,
        public string $clientName,
        public string $clientPhone,
        public int $daysSinceVisit,
        public \DateTimeImmutable $lastVisitAt,
        public ?\DateTimeImmutable $lastRemindedAt,
        public string $message,
    ) {
    }
}
