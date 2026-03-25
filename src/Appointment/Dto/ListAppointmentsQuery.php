<?php

declare(strict_types=1);

namespace App\Appointment\Dto;

final readonly class ListAppointmentsQuery
{
    public ?string $dateFrom;
    public ?string $dateTo;
    /** @var string[] */
    public array $status;
    public ?string $clientId;
    public ?string $cursor;
    public int $limit;

    /**
     * @param string|string[]|null $status
     */
    public function __construct(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string|array|null $status = null,
        ?string $clientId = null,
        ?string $cursor = null,
        int $limit = 20,
    ) {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = \is_string($status) ? [$status] : ($status ?? []);
        $this->clientId = $clientId;
        $this->cursor = $cursor;
        $this->limit = min(max($limit, 1), 100);
    }
}
