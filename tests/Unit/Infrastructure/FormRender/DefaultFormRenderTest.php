<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\FormRender;

use Letkode\FormSchema\Infrastructure\FormRender\DefaultFormRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DefaultFormRenderTest extends TestCase
{
    private DefaultFormRender $render;

    protected function setUp(): void
    {
        $this->render = new DefaultFormRender();
    }

    #[Test]
    public function testGetNameReturnsDefault(): void
    {
        self::assertSame('default', DefaultFormRender::getName());
    }

    #[Test]
    public function testRenderMetaReturnsEmptyArray(): void
    {
        self::assertSame([], $this->render->renderMeta([]));
    }

    #[Test]
    public function testRenderMetaIgnoresParameters(): void
    {
        self::assertSame([], $this->render->renderMeta(['foo' => 'bar']));
    }
}
