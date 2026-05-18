<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Registry;

use Letkode\FormSchema\Domain\Contract\SectionRenderInterface;
use Letkode\FormSchema\Domain\Exception\UnknownRenderException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class SectionRenderRegistry
{
    /** @var array<string, SectionRenderInterface> */
    private array $renders = [];

    public function __construct(
        #[AutowireIterator('form_schema.section_render', defaultPriorityMethod: 'getPriority')]
        iterable $taggedRenders,
    ) {
        foreach ($taggedRenders as $render) {
            $this->renders[$render::getName()] = $render;
        }
    }

    public function get(string $name): SectionRenderInterface
    {
        return $this->renders[$name] ?? throw new UnknownRenderException('section', $name);
    }

    public function has(string $name): bool
    {
        return isset($this->renders[$name]);
    }

    /** @return array<string, SectionRenderInterface> */
    public function all(): array
    {
        return $this->renders;
    }
}
