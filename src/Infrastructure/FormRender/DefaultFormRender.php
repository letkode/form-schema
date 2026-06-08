<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FormRender;

use Letkode\FormSchema\Attribute\AsFormRender;
use Letkode\FormSchema\Domain\Contract\FormRenderInterface;

#[AsFormRender]
final class DefaultFormRender implements FormRenderInterface
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
