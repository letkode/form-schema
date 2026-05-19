<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\InteractionHandler;

use Letkode\FormSchema\Infrastructure\InteractionHandler\AjaxValidateInteractionHandler;
use Letkode\FormSchema\Infrastructure\InteractionHandler\ComputeInteractionHandler;
use Letkode\FormSchema\Infrastructure\InteractionHandler\FilterOptionsInteractionHandler;
use Letkode\FormSchema\Infrastructure\InteractionHandler\SetDateConstraintInteractionHandler;
use Letkode\FormSchema\Infrastructure\InteractionHandler\SetValueInteractionHandler;
use Letkode\FormSchema\Infrastructure\InteractionHandler\ToggleRequiredInteractionHandler;
use Letkode\FormSchema\Infrastructure\InteractionHandler\ToggleVisibilityInteractionHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InteractionHandlerDefaultParamsTest extends TestCase
{
    #[Test]
    public function testFilterOptionsDefaults(): void
    {
        $handler = new FilterOptionsInteractionHandler();

        self::assertSame('filter_options', $handler::getName());
        self::assertSame(['mode' => 'server'], $handler->getDefaultParams());
    }

    #[Test]
    public function testToggleVisibilityDefaults(): void
    {
        $handler = new ToggleVisibilityInteractionHandler();

        self::assertSame('toggle_visibility', $handler::getName());
        self::assertSame([], $handler->getDefaultParams());
    }

    #[Test]
    public function testToggleRequiredDefaults(): void
    {
        $handler = new ToggleRequiredInteractionHandler();

        self::assertSame('toggle_required', $handler::getName());
        self::assertSame([], $handler->getDefaultParams());
    }

    #[Test]
    public function testSetValueDefaults(): void
    {
        $handler = new SetValueInteractionHandler();

        self::assertSame('set_value', $handler::getName());
        self::assertSame([], $handler->getDefaultParams());
    }

    #[Test]
    public function testAjaxValidateDefaults(): void
    {
        $handler = new AjaxValidateInteractionHandler();

        self::assertSame('ajax_validate', $handler::getName());
        self::assertSame(['method' => 'GET', 'debounce' => 0], $handler->getDefaultParams());
    }

    #[Test]
    public function testSetDateConstraintDefaults(): void
    {
        $handler = new SetDateConstraintInteractionHandler();

        self::assertSame('set_date_constraint', $handler::getName());
        self::assertSame(['constraint' => 'min'], $handler->getDefaultParams());
    }

    #[Test]
    public function testComputeDefaults(): void
    {
        $handler = new ComputeInteractionHandler();

        self::assertSame('compute', $handler::getName());
        self::assertSame(['decimals' => null], $handler->getDefaultParams());
    }

    #[Test]
    public function testStoredParamsTakePrecedenceOverDefaults(): void
    {
        $handler = new AjaxValidateInteractionHandler();
        $stored = ['endpoint' => '/api/check', 'method' => 'POST', 'debounce' => 500];

        $merged = array_replace($handler->getDefaultParams(), $stored);

        self::assertSame('POST', $merged['method']);
        self::assertSame(500, $merged['debounce']);
        self::assertSame('/api/check', $merged['endpoint']);
    }
}
