<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\GroupRender;

use Letkode\FormSchema\Attribute\AsGroupRender;
use Letkode\FormSchema\Domain\Contract\GroupRenderInterface;

#[AsGroupRender]
final class MatrixGroupRender implements GroupRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'matrix';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'rows' => $parameters['matrix']['rows'] ?? [],
            'cols' => $parameters['matrix']['cols'] ?? [],
        ];
    }
}
