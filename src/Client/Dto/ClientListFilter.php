<?php

declare(strict_types=1);

namespace App\Client\Dto;

final readonly class ClientListFilter
{
    private const ALLOWED_SORTS = ['created_at', 'last_visit_at', 'last_name'];
    private const ALLOWED_DIRECTIONS = ['asc', 'desc'];

    public string $search;
    public ?string $cursor;
    public int $limit;
    public string $sort;
    public string $direction;

    public function __construct(
        string $search = '',
        ?string $cursor = null,
        int $limit = 20,
        string $sort = 'created_at',
        string $direction = 'desc',
    ) {
        $this->search = $search;
        $this->cursor = $cursor;
        $this->limit = min(max($limit, 1), 100);
        $this->sort = in_array($sort, self::ALLOWED_SORTS, true) ? $sort : 'created_at';
        $this->direction = in_array($direction, self::ALLOWED_DIRECTIONS, true) ? $direction : 'desc';
    }
}