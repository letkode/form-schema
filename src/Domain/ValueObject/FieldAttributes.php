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
        public bool $show = true,
        public bool $edit = true,
        public bool $create = true,
        private array $dynamic = [],
    ) {
    }

    public static function default(): self
    {
        return new self();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            required: $data['required'] ?? false,
            readonly: $data['readonly'] ?? false,
            unique: UniqueRule::fromArray($data['unique'] ?? []),
            filter: FilterRule::fromArray($data['filter'] ?? []),
            show: $data['show'] ?? true,
            edit: $data['edit'] ?? true,
            create: $data['create'] ?? true,
            dynamic: array_diff_key(
                $data,
                array_flip(['required', 'readonly', 'unique', 'filter', 'show', 'edit', 'create']),
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'required' => $this->required,
            'readonly' => $this->readonly,
            'unique' => $this->unique->toArray(),
            'filter' => $this->filter->toArray(),
            'show' => $this->show,
            'edit' => $this->edit,
            'create' => $this->create,
            ...$this->dynamic,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->dynamic[$key] ?? $default;
    }
}
