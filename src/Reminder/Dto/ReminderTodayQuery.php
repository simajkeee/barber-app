<?php

declare(strict_types=1);

namespace App\Reminder\Dto;

final readonly class ReminderTodayQuery
{
    public int $limit;
    public ?string $cursor;

    public function __construct(
        int $limit = 50,
        ?string $cursor = null,
    ) {
        $this->limit = min(max($limit, 1), 100);
        $this->cursor = $cursor;
    }
}
