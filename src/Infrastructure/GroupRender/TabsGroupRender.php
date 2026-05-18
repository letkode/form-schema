<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\GroupRender;

use Letkode\FormSchema\Attribute\AsGroupRender;
use Letkode\FormSchema\Domain\Contract\GroupRenderInterface;

#[AsGroupRender]
final class TabsGroupRender implements GroupRenderInterface
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
            'position' => $parameters['tabs']['position'] ?? 'top',
            'lazy_load' => $parameters['tabs']['lazy_load'] ?? false,
        ];
    }
}
