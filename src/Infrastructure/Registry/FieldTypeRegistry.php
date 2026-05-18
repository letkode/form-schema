<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Registry;

use Letkode\FormSchema\Domain\Contract\FieldTypeInterface;
use Letkode\FormSchema\Domain\Exception\UnknownFieldTypeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class FieldTypeRegistry
{
    /** @var array<string, FieldTypeInterface> */
    private array $types = [];

    public function __construct(
        #[AutowireIterator('form_schema.field_type', defaultPriorityMethod: 'getPriority')]
        iterable $taggedTypes,
    ) {
        foreach ($taggedTypes as $type) {
            $this->types[$type::getName()] = $type;
        }
    }

    public function get(string $name): FieldTypeInterface
    {
        return $this->types[$name] ?? throw new UnknownFieldTypeException($name);
    }

    public function has(string $name): bool
    {
        return isset($this->types[$name]);
    }

    /** @return array<string, FieldTypeInterface> */
    public function all(): array
    {
        return $this->types;
    }

    public function hasAnyOptionsType(): bool
    {
        return array_any($this->types, static fn ($t) => $t->takesOptions());
    }
}
