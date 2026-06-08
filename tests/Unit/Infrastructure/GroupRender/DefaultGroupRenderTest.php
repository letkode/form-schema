<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\GroupRender;

use Letkode\FormSchema\Infrastructure\GroupRender\DefaultGroupRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DefaultGroupRenderTest extends TestCase
{
    private DefaultGroupRender $render;

    protected function setUp(): void
    {
        $this->render = new DefaultGroupRender();
    }

    #[Test]
    public function testGetNameReturnsDefault(): void
    {
        self::assertSame('default', DefaultGroupRender::getName());
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
