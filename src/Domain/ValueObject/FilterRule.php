<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\ValueObject;

final readonly class FilterRule
{
    public function __construct(
        public bool $enabled = false,
        public string|null $key = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: $data['enabled'] ?? false,
            key: $data['key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'key' => $this->key,
        ];
    }
}
