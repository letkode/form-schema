<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\Registry;

use Letkode\FormSchema\Domain\Exception\UnknownRenderException;
use Letkode\FormSchema\Infrastructure\FormRender\SimpleFormRender;
use Letkode\FormSchema\Infrastructure\FormRender\TabsFormRender;
use Letkode\FormSchema\Infrastructure\Registry\FormRenderRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormRenderRegistryTest extends TestCase
{
    private FormRenderRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new FormRenderRegistry(new \ArrayIterator([
            new SimpleFormRender(),
            new TabsFormRender(),
        ]));
    }

    #[Test]
    public function testGetReturnsRegisteredRender(): void
    {
        $render = $this->registry->get('simple');

        self::assertInstanceOf(SimpleFormRender::class, $render);
    }

    #[Test]
    public function testGetThrowsForUnknownRender(): void
    {
        $this->expectException(UnknownRenderException::class);

        $this->registry->get('nonexistent');
    }

    #[Test]
    public function testHasReturnsTrueForRegisteredRender(): void
    {
        self::assertTrue($this->registry->has('simple'));
        self::assertTrue($this->registry->has('tabs'));
    }

    #[Test]
    public function testHasReturnsFalseForUnknownRender(): void
    {
        self::assertFalse($this->registry->has('nonexistent'));
    }

    #[Test]
    public function testAllReturnsAllRenders(): void
    {
        $all = $this->registry->all();

        self::assertCount(2, $all);
        self::assertArrayHasKey('simple', $all);
        self::assertArrayHasKey('tabs', $all);
    }
}
