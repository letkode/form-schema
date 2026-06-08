<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\SectionRender;

use Letkode\FormSchema\Infrastructure\SectionRender\CollapsibleSectionRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CollapsibleSectionRenderTest extends TestCase
{
    private CollapsibleSectionRender $render;

    protected function setUp(): void
    {
        $this->render = new CollapsibleSectionRender();
    }

    #[Test]
    public function testGetNameReturnsCollapsible(): void
    {
        self::assertSame('collapsible', CollapsibleSectionRender::getName());
    }

    #[Test]
    public function testRenderMetaReturnsDefaults(): void
    {
        $meta = $this->render->renderMeta([]);

        self::assertSame(['default_collapsed' => false], $meta);
    }

    #[Test]
    public function testRenderMetaUsesProvidedValue(): void
    {
        $meta = $this->render->renderMeta([
            'collapsible' => ['default_collapsed' => true],
        ]);

        self::assertSame(['default_collapsed' => true], $meta);
    }
}
