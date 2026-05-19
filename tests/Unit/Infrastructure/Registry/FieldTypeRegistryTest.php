<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\Registry;

use Letkode\FormSchema\Domain\Exception\UnknownFieldTypeException;
use Letkode\FormSchema\Infrastructure\FieldType\SelectFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\StringFieldType;
use Letkode\FormSchema\Infrastructure\Registry\FieldTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FieldTypeRegistryTest extends TestCase
{
    private FieldTypeRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new FieldTypeRegistry(new \ArrayIterator([
            new StringFieldType(),
            new SelectFieldType(),
        ]));
    }

    #[Test]
    public function testGetReturnsRegisteredType(): void
    {
        $type = $this->registry->get('string');

        self::assertInstanceOf(StringFieldType::class, $type);
    }

    #[Test]
    public function testGetThrowsForUnknownType(): void
    {
        $this->expectException(UnknownFieldTypeException::class);

        $this->registry->get('nonexistent');
    }

    #[Test]
    public function testHasReturnsTrueForRegisteredType(): void
    {
        self::assertTrue($this->registry->has('string'));
        self::assertTrue($this->registry->has('select'));
    }

    #[Test]
    public function testHasReturnsFalseForUnknownType(): void
    {
        self::assertFalse($this->registry->has('nonexistent'));
    }

    #[Test]
    public function testAllReturnsAllTypes(): void
    {
        $all = $this->registry->all();

        self::assertCount(2, $all);
        self::assertArrayHasKey('string', $all);
        self::assertArrayHasKey('select', $all);
    }
}
