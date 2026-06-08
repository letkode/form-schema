<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\SectionRender;

use Letkode\FormSchema\Attribute\AsSectionRender;
use Letkode\FormSchema\Domain\Contract\SectionRenderInterface;

#[AsSectionRender]
final class TabsSectionRender implements SectionRenderInterface
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
