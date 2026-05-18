<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\SectionRender;

use Letkode\FormSchema\Attribute\AsSectionRender;
use Letkode\FormSchema\Domain\Contract\SectionRenderInterface;

#[AsSectionRender]
final class SimpleSectionRender implements SectionRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'simple';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [];
    }
}
