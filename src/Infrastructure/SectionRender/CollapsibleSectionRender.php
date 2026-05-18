<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\SectionRender;

use Letkode\FormSchema\Attribute\AsSectionRender;
use Letkode\FormSchema\Domain\Contract\SectionRenderInterface;

#[AsSectionRender]
final class CollapsibleSectionRender implements SectionRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'collapsible';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'default_collapsed' => $parameters['collapsible']['default_collapsed'] ?? false,
        ];
    }
}
