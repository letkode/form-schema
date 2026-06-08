<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\FormRender;

use Letkode\FormSchema\Infrastructure\FormRender\TabsFormRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TabsFormRenderTest extends TestCase
{
    private TabsFormRender $render;

    protected function setUp(): void
    {
        $this->render = new TabsFormRender();
    }

    #[Test]
    public function testGetNameReturnsTabs(): void
    {
        self::assertSame('tabs', TabsFormRender::getName());
    }

    #[Test]
    public function testRenderMetaReturnsDefaults(): void
    {
        $meta = $this->render->renderMeta([]);

        self::assertSame([
            'orientation' => 'horizontal',
            'position' => 'top',
            'lazy_load' => false,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaUsesProvidedValues(): void
    {
        $meta = $this->render->renderMeta([
            'tabs' => [
                'orientation' => 'vertical',
                'position' => 'bottom',
                'lazy_load' => true,
            ],
        ]);

        self::assertSame([
            'orientation' => 'vertical',
            'position' => 'bottom',
            'lazy_load' => true,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaFallsBackToDefaultsOnPartialParams(): void
    {
        $meta = $this->render->renderMeta([
            'tabs' => ['lazy_load' => true],
        ]);

        self::assertSame('horizontal', $meta['orientation']);
        self::assertSame('top', $meta['position']);
        self::assertTrue($meta['lazy_load']);
    }
}
