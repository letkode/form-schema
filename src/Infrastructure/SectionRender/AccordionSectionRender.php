<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\SectionRender;

use Letkode\FormSchema\Attribute\AsSectionRender;
use Letkode\FormSchema\Domain\Contract\SectionRenderInterface;

#[AsSectionRender]
final class AccordionSectionRender implements SectionRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'accordion';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'allow_multiple_open' => $parameters['accordion']['allow_multiple_open'] ?? false,
            'first_open' => $parameters['accordion']['first_open'] ?? true,
        ];
    }
}
