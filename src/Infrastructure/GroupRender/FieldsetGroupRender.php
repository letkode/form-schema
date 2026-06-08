<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\GroupRender;

use Letkode\FormSchema\Attribute\AsGroupRender;
use Letkode\FormSchema\Domain\Contract\GroupRenderInterface;

#[AsGroupRender]
final class FieldsetGroupRender implements GroupRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'fieldset';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'legend' => (bool) ($parameters['legend'] ?? true),
            'legend_custom' => $parameters['legend_custom'] ?? null,
        ];
    }
}
