<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\SectionRender;

use Letkode\FormSchema\Attribute\AsSectionRender;
use Letkode\FormSchema\Domain\Contract\SectionRenderInterface;

#[AsSectionRender]
final class DefaultSectionRender implements SectionRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'default';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [];
    }
}
