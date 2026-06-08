<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\FormRender;

use Letkode\FormSchema\Infrastructure\FormRender\StepperFormRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StepperFormRenderTest extends TestCase
{
    private StepperFormRender $render;

    protected function setUp(): void
    {
        $this->render = new StepperFormRender();
    }

    #[Test]
    public function testGetNameReturnsStepper(): void
    {
        self::assertSame('stepper', StepperFormRender::getName());
    }

    #[Test]
    public function testRenderMetaReturnsDefaults(): void
    {
        $meta = $this->render->renderMeta([]);

        self::assertSame([
            'orientation' => 'horizontal',
            'show_progress' => true,
            'allow_skip' => false,
            'persist_on_navigate' => true,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaUsesProvidedValues(): void
    {
        $meta = $this->render->renderMeta([
            'stepper' => [
                'orientation' => 'vertical',
                'show_progress' => false,
                'allow_skip' => true,
                'persist_on_navigate' => false,
            ],
        ]);

        self::assertSame([
            'orientation' => 'vertical',
            'show_progress' => false,
            'allow_skip' => true,
            'persist_on_navigate' => false,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaFallsBackToDefaultsOnPartialParams(): void
    {
        $meta = $this->render->renderMeta([
            'stepper' => ['orientation' => 'vertical'],
        ]);

        self::assertSame('vertical', $meta['orientation']);
        self::assertTrue($meta['show_progress']);
        self::assertFalse($meta['allow_skip']);
        self::assertTrue($meta['persist_on_navigate']);
    }
}
