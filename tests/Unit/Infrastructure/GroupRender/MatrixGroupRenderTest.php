<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\GroupRender;

use Letkode\FormSchema\Infrastructure\GroupRender\MatrixGroupRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MatrixGroupRenderTest extends TestCase
{
    private MatrixGroupRender $render;

    protected function setUp(): void
    {
        $this->render = new MatrixGroupRender();
    }

    #[Test]
    public function testGetNameReturnsMatrix(): void
    {
        self::assertSame('matrix', MatrixGroupRender::getName());
    }

    #[Test]
    public function testRenderMetaReturnsEmptyArraysAsDefaults(): void
    {
        $meta = $this->render->renderMeta([]);

        self::assertSame(['rows' => [], 'cols' => []], $meta);
    }

    #[Test]
    public function testRenderMetaUsesProvidedValues(): void
    {
        $meta = $this->render->renderMeta([
            'matrix' => [
                'rows' => ['Email', 'SMS', 'Push'],
                'cols' => ['Daily', 'Weekly', 'Monthly'],
            ],
        ]);

        self::assertSame(['Email', 'SMS', 'Push'], $meta['rows']);
        self::assertSame(['Daily', 'Weekly', 'Monthly'], $meta['cols']);
    }

    #[Test]
    public function testRenderMetaFallsBackToDefaultsOnPartialParams(): void
    {
        $meta = $this->render->renderMeta([
            'matrix' => ['rows' => ['A', 'B']],
        ]);

        self::assertSame(['A', 'B'], $meta['rows']);
        self::assertSame([], $meta['cols']);
    }
}
