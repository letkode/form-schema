<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\ValueObject;

final readonly class FieldAttributes
{
    public function __construct(
        public bool $required = false,
        public bool $readonly = false,
        public UniqueRule $unique = new UniqueRule(),
        public FilterRule $filter = new FilterRule(),
        public array $actions = [],
        private array $dynamic = [],
    ) {
    }

    public static function default(): self
    {
        return new self();
    }

    public static function fromArray(array $data): self
    {
        $knownKeys = ['required', 'readonly', 'unique', 'filter', 'actions'];

        return new self(
            required: $data['required'] ?? false,
            readonly: $data['readonly'] ?? false,
            unique: UniqueRule::fromArray($data['unique'] ?? []),
            filter: FilterRule::fromArray($data['filter'] ?? []),
            actions: $data['actions'] ?? [],
            dynamic: array_diff_key($data, array_flip($knownKeys)),
        );
    }

    public function toArray(): array
    {
        return [
            'required' => $this->required,
            'readonly' => $this->readonly,
            'unique' => $this->unique->toArray(),
            'filter' => $this->filter->toArray(),
            'actions' => $this->actions,
            ...$this->dynamic,
        ];
    }

    public function withActionOverrides(string $context): self
    {
        $overrides = $this->actions[$context] ?? [];
        unset($overrides['enabled']);

        if ([] === $overrides) {
            return $this;
        }

        return new self(
            required: $overrides['required'] ?? $this->required,
            readonly: $overrides['readonly'] ?? $this->readonly,
            unique: $this->unique,
            filter: $this->filter,
            actions: $this->actions,
            dynamic: array_merge($this->dynamic, array_diff_key($overrides, array_flip(['required', 'readonly']))),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->dynamic[$key] ?? $default;
    }
}
