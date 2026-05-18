<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\ValueObject;

final readonly class SectionParameters
{
    public function __construct(
        public string|null $typeRender = null,
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
            typeRender: $data['type_render'] ?? null,
            dynamic: array_diff_key(
                $data,
                array_flip(['type_render']),
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'type_render' => $this->typeRender,
            ...$this->dynamic,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->dynamic[$key] ?? $default;
    }
}
