<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\GroupRender;

use Letkode\FormSchema\Attribute\AsGroupRender;
use Letkode\FormSchema\Domain\Contract\GroupRenderInterface;

#[AsGroupRender]
final class SimpleGroupRender implements GroupRenderInterface
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
