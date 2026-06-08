<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\SectionRender;

use Letkode\FormSchema\Infrastructure\SectionRender\AccordionSectionRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccordionSectionRenderTest extends TestCase
{
    private AccordionSectionRender $render;

    protected function setUp(): void
    {
        $this->render = new AccordionSectionRender();
    }

    #[Test]
    public function testGetNameReturnsAccordion(): void
    {
        self::assertSame('accordion', AccordionSectionRender::getName());
    }

    #[Test]
    public function testRenderMetaReturnsDefaults(): void
    {
        $meta = $this->render->renderMeta([]);

        self::assertSame([
            'allow_multiple_open' => false,
            'first_open' => true,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaUsesProvidedValues(): void
    {
        $meta = $this->render->renderMeta([
            'accordion' => [
                'allow_multiple_open' => true,
                'first_open' => false,
            ],
        ]);

        self::assertSame([
            'allow_multiple_open' => true,
            'first_open' => false,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaFallsBackToDefaultsOnPartialParams(): void
    {
        $meta = $this->render->renderMeta([
            'accordion' => ['allow_multiple_open' => true],
        ]);

        self::assertTrue($meta['allow_multiple_open']);
        self::assertTrue($meta['first_open']);
    }
}
