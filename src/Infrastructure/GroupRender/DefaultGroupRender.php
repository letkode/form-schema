<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\GroupRender;

use Letkode\FormSchema\Attribute\AsGroupRender;
use Letkode\FormSchema\Domain\Contract\GroupRenderInterface;

#[AsGroupRender]
final class DefaultGroupRender implements GroupRenderInterface
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
