<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Registry;

use Letkode\FormSchema\Domain\Contract\OptionsSourceInterface;
use Letkode\FormSchema\Domain\Exception\UnknownOptionsSourceException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class OptionsSourceRegistry
{
    /** @var array<string, OptionsSourceInterface> */
    private array $sources = [];

    public function __construct(
        #[AutowireIterator('form_schema.options_source', defaultPriorityMethod: 'getPriority')]
        iterable $taggedSources,
    ) {
        foreach ($taggedSources as $source) {
            $this->sources[$source::getName()] = $source;
        }
    }

    public function get(string $name): OptionsSourceInterface
    {
        return $this->sources[$name] ?? throw new UnknownOptionsSourceException($name);
    }

    public function has(string $name): bool
    {
        return isset($this->sources[$name]);
    }

    /** @return array<string, OptionsSourceInterface> */
    public function all(): array
    {
        return $this->sources;
    }
}
