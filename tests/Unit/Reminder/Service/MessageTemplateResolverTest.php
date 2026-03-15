<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reminder\Service;

use App\Reminder\Service\MessageTemplateResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MessageTemplateResolver::class)]
final class MessageTemplateResolverTest extends TestCase
{
    private MessageTemplateResolver $sut;

    protected function setUp(): void
    {
        $this->sut = new MessageTemplateResolver();
    }

    #[Test]
    public function testReplacesAllPlaceholders(): void
    {
        $template = 'Chào {client_name}! Đã {days_since_visit} ngày kể từ lần cắt tóc cuối tại {shop_name}. Phone: {client_phone}';

        $result = $this->sut->resolve($template, [
            'client_name' => 'Nguyễn Văn A',
            'shop_name' => 'BarberPro',
            'days_since_visit' => '45',
            'client_phone' => '+84912345678',
        ]);

        self::assertSame(
            'Chào Nguyễn Văn A! Đã 45 ngày kể từ lần cắt tóc cuối tại BarberPro. Phone: +84912345678',
            $result,
        );
    }

    #[Test]
    public function testLeavesUnknownPlaceholdersAsIs(): void
    {
        $template = 'Hello {client_name}! {unknown_placeholder} is here.';

        $result = $this->sut->resolve($template, [
            'client_name' => 'John',
        ]);

        self::assertSame('Hello John! {unknown_placeholder} is here.', $result);
    }

    #[Test]
    public function testEmptyTemplate(): void
    {
        $result = $this->sut->resolve('', ['client_name' => 'John']);

        self::assertSame('', $result);
    }

    #[Test]
    public function testTemplateWithNoPlaceholders(): void
    {
        $result = $this->sut->resolve('No placeholders here.', [
            'client_name' => 'John',
        ]);

        self::assertSame('No placeholders here.', $result);
    }

    #[Test]
    public function testEmptyVariables(): void
    {
        $template = 'Hello {client_name}!';

        $result = $this->sut->resolve($template, []);

        self::assertSame('Hello {client_name}!', $result);
    }

    #[Test]
    public function testReplacesMultipleOccurrencesOfSamePlaceholder(): void
    {
        $template = '{client_name} is great. We love {client_name}!';

        $result = $this->sut->resolve($template, [
            'client_name' => 'John',
        ]);

        self::assertSame('John is great. We love John!', $result);
    }
}
