<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\SectionRender;

use Letkode\FormSchema\Infrastructure\SectionRender\DefaultSectionRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DefaultSectionRenderTest extends TestCase
{
    private DefaultSectionRender $render;

    protected function setUp(): void
    {
        $this->render = new DefaultSectionRender();
    }

    #[Test]
    public function testGetNameReturnsDefault(): void
    {
        self::assertSame('default', DefaultSectionRender::getName());
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
