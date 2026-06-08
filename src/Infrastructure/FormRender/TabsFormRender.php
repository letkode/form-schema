<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FormRender;

use Letkode\FormSchema\Attribute\AsFormRender;
use Letkode\FormSchema\Domain\Contract\FormRenderInterface;

#[AsFormRender]
final class TabsFormRender implements FormRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'tabs';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'orientation' => $parameters['tabs']['orientation'] ?? 'horizontal',
            'position' => $parameters['tabs']['position'] ?? 'top',
            'lazy_load' => $parameters['tabs']['lazy_load'] ?? false,
        ];
    }
}
