<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\Registry;

use Letkode\FormSchema\Domain\Exception\UnknownInteractionHandlerException;
use Letkode\FormSchema\Infrastructure\InteractionHandler\AjaxValidateInteractionHandler;
use Letkode\FormSchema\Infrastructure\InteractionHandler\FilterOptionsInteractionHandler;
use Letkode\FormSchema\Infrastructure\InteractionHandler\ComputeInteractionHandler;
use Letkode\FormSchema\Infrastructure\Registry\InteractionHandlerRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InteractionHandlerRegistryTest extends TestCase
{
    private InteractionHandlerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new InteractionHandlerRegistry(new \ArrayIterator([
            new FilterOptionsInteractionHandler(),
            new AjaxValidateInteractionHandler(),
            new ComputeInteractionHandler(),
        ]));
    }

    #[Test]
    public function testGetReturnsRegisteredHandler(): void
    {
        $handler = $this->registry->get('filter_options');

        self::assertInstanceOf(FilterOptionsInteractionHandler::class, $handler);
    }

    #[Test]
    public function testGetThrowsForUnknownHandler(): void
    {
        $this->expectException(UnknownInteractionHandlerException::class);

        $this->registry->get('nonexistent_action');
    }

    #[Test]
    public function testHasReturnsTrueForRegisteredHandler(): void
    {
        self::assertTrue($this->registry->has('filter_options'));
        self::assertTrue($this->registry->has('ajax_validate'));
        self::assertTrue($this->registry->has('compute'));
    }

    #[Test]
    public function testHasReturnsFalseForUnknownHandler(): void
    {
        self::assertFalse($this->registry->has('nonexistent_action'));
    }

    #[Test]
    public function testAllReturnsAllHandlers(): void
    {
        $all = $this->registry->all();

        self::assertCount(3, $all);
        self::assertArrayHasKey('filter_options', $all);
        self::assertArrayHasKey('ajax_validate', $all);
        self::assertArrayHasKey('compute', $all);
    }
}
