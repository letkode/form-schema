<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\GroupRender;

use Letkode\FormSchema\Infrastructure\GroupRender\FieldsetGroupRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FieldsetGroupRenderTest extends TestCase
{
    private FieldsetGroupRender $render;

    protected function setUp(): void
    {
        $this->render = new FieldsetGroupRender();
    }

    #[Test]
    public function testGetNameReturnsFieldset(): void
    {
        self::assertSame('fieldset', FieldsetGroupRender::getName());
    }

    #[Test]
    public function testRenderMetaReturnsDefaults(): void
    {
        $meta = $this->render->renderMeta([]);

        self::assertSame([
            'legend' => true,
            'legend_custom' => null,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaUsesProvidedValues(): void
    {
        $meta = $this->render->renderMeta([
            'legend' => false,
            'legend_custom' => 'Custom Legend',
        ]);

        self::assertSame([
            'legend' => false,
            'legend_custom' => 'Custom Legend',
        ], $meta);
    }

    #[Test]
    public function testLegendIsCastToBool(): void
    {
        $meta = $this->render->renderMeta(['legend' => 0]);

        self::assertFalse($meta['legend']);
        self::assertIsBool($meta['legend']);
    }
}
