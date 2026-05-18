<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\ValueObject;

final readonly class FieldParameters
{
    public function __construct(
        public string|null $placeholder = null,
        public mixed $defaultValue = null,
        public array $style = [],
        public array $setOptions = [],
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
            placeholder: $data['placeholder'] ?? null,
            defaultValue: $data['default_value'] ?? null,
            style: $data['style'] ?? [],
            setOptions: $data['set_options'] ?? [],
            dynamic: array_diff_key(
                $data,
                array_flip(['placeholder', 'default_value', 'style', 'set_options']),
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'placeholder' => $this->placeholder,
            'default_value' => $this->defaultValue,
            'style' => $this->style,
            'set_options' => $this->setOptions,
            ...$this->dynamic,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->dynamic[$key] ?? $default;
    }
}
