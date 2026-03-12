<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Dto;

use App\Client\Dto\ClientListFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientListFilter::class)]
final class ClientListFilterTest extends TestCase
{
    #[Test]
    public function testDefaultValues(): void
    {
        $filter = new ClientListFilter();

        self::assertSame('', $filter->search);
        self::assertNull($filter->cursor);
        self::assertSame(20, $filter->limit);
        self::assertSame('created_at', $filter->sort);
        self::assertSame('desc', $filter->direction);
    }

    #[Test]
    public function testLimitClampedToMinimum1(): void
    {
        $filter = new ClientListFilter(limit: 0);
        self::assertSame(1, $filter->limit);

        $filter = new ClientListFilter(limit: -10);
        self::assertSame(1, $filter->limit);
    }

    #[Test]
    public function testLimitClampedToMaximum100(): void
    {
        $filter = new ClientListFilter(limit: 200);
        self::assertSame(100, $filter->limit);

        $filter = new ClientListFilter(limit: 101);
        self::assertSame(100, $filter->limit);
    }

    #[Test]
    public function testLimitWithinRangeIsPreserved(): void
    {
        $filter = new ClientListFilter(limit: 50);
        self::assertSame(50, $filter->limit);
    }

    #[Test]
    public function testLimitBoundaryValues(): void
    {
        self::assertSame(1, (new ClientListFilter(limit: 1))->limit);
        self::assertSame(100, (new ClientListFilter(limit: 100))->limit);
    }

    #[Test]
    public function testInvalidSortFallsBackToCreatedAt(): void
    {
        $filter = new ClientListFilter(sort: 'invalid_column');
        self::assertSame('created_at', $filter->sort);
    }

    #[Test]
    public function testAllowedSortValues(): void
    {
        self::assertSame('created_at', (new ClientListFilter(sort: 'created_at'))->sort);
        self::assertSame('last_visit_at', (new ClientListFilter(sort: 'last_visit_at'))->sort);
        self::assertSame('last_name', (new ClientListFilter(sort: 'last_name'))->sort);
    }

    #[Test]
    public function testInvalidDirectionFallsBackToDesc(): void
    {
        $filter = new ClientListFilter(direction: 'invalid');
        self::assertSame('desc', $filter->direction);
    }

    #[Test]
    public function testAllowedDirectionValues(): void
    {
        self::assertSame('asc', (new ClientListFilter(direction: 'asc'))->direction);
        self::assertSame('desc', (new ClientListFilter(direction: 'desc'))->direction);
    }

    #[Test]
    public function testSearchAndCursorArePassedThrough(): void
    {
        $filter = new ClientListFilter(search: 'Nguyen', cursor: 'abc123');

        self::assertSame('Nguyen', $filter->search);
        self::assertSame('abc123', $filter->cursor);
    }
}