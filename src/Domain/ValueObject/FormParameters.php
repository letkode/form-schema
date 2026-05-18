<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\ValueObject;

final readonly class FormParameters
{
    public function __construct(
        public string|null $typeRender = null,
        public string|null $defaultLocale = null,
        public array $availableLocales = [],
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
            defaultLocale: $data['default_locale'] ?? null,
            availableLocales: $data['available_locales'] ?? [],
            dynamic: array_diff_key(
                $data,
                array_flip(['type_render', 'default_locale', 'available_locales']),
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'type_render' => $this->typeRender,
            'default_locale' => $this->defaultLocale,
            'available_locales' => $this->availableLocales,
            ...$this->dynamic,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->dynamic[$key] ?? $default;
    }
}
