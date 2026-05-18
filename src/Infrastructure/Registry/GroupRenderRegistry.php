<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Registry;

use Letkode\FormSchema\Domain\Contract\GroupRenderInterface;
use Letkode\FormSchema\Domain\Exception\UnknownRenderException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class GroupRenderRegistry
{
    /** @var array<string, GroupRenderInterface> */
    private array $renders = [];

    public function __construct(
        #[AutowireIterator('form_schema.group_render', defaultPriorityMethod: 'getPriority')]
        iterable $taggedRenders,
    ) {
        foreach ($taggedRenders as $render) {
            $this->renders[$render::getName()] = $render;
        }
    }

    public function get(string $name): GroupRenderInterface
    {
        return $this->renders[$name] ?? throw new UnknownRenderException('group', $name);
    }

    public function has(string $name): bool
    {
        return isset($this->renders[$name]);
    }

    /** @return array<string, GroupRenderInterface> */
    public function all(): array
    {
        return $this->renders;
    }
}
