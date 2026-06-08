<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\SectionRender;

use Letkode\FormSchema\Infrastructure\SectionRender\TabsSectionRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TabsSectionRenderTest extends TestCase
{
    private TabsSectionRender $render;

    protected function setUp(): void
    {
        $this->render = new TabsSectionRender();
    }

    #[Test]
    public function testGetNameReturnsTabs(): void
    {
        self::assertSame('tabs', TabsSectionRender::getName());
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
                'position' => 'left',
                'lazy_load' => true,
            ],
        ]);

        self::assertSame([
            'orientation' => 'vertical',
            'position' => 'left',
            'lazy_load' => true,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaFallsBackToDefaultsOnPartialParams(): void
    {
        $meta = $this->render->renderMeta([
            'tabs' => ['position' => 'bottom'],
        ]);

        self::assertSame('horizontal', $meta['orientation']);
        self::assertSame('bottom', $meta['position']);
        self::assertFalse($meta['lazy_load']);
    }
}
