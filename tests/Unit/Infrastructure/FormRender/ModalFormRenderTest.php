<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\FormRender;

use Letkode\FormSchema\Infrastructure\FormRender\ModalFormRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ModalFormRenderTest extends TestCase
{
    private ModalFormRender $render;

    protected function setUp(): void
    {
        $this->render = new ModalFormRender();
    }

    #[Test]
    public function testGetNameReturnsModal(): void
    {
        self::assertSame('modal', ModalFormRender::getName());
    }

    #[Test]
    public function testRenderMetaReturnsDefaults(): void
    {
        $meta = $this->render->renderMeta([]);

        self::assertSame([
            'size' => 'md',
            'position' => 'center',
            'dismissible' => true,
            'show_close_button' => true,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaRespectsCustomParameters(): void
    {
        $meta = $this->render->renderMeta([
            'modal' => [
                'size' => 'xl',
                'position' => 'drawer-right',
                'dismissible' => false,
                'show_close_button' => false,
            ],
        ]);

        self::assertSame([
            'size' => 'xl',
            'position' => 'drawer-right',
            'dismissible' => false,
            'show_close_button' => false,
        ], $meta);
    }

    #[Test]
    public function testRenderMetaMergesPartialParameters(): void
    {
        $meta = $this->render->renderMeta([
            'modal' => ['size' => 'lg'],
        ]);

        self::assertSame('lg', $meta['size']);
        self::assertSame('center', $meta['position']);
        self::assertTrue($meta['dismissible']);
        self::assertTrue($meta['show_close_button']);
    }
}
