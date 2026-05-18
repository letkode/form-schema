<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\DTO;

final readonly class FieldDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $tag,
        public string $type,
        public string|null $description,
        public array $attributes,
        public array $parameters,
        public int $position,
        public bool $enabled,
        public string|null $placeholder,
        public mixed $defaultValue,
        public array $style,
        public array $options,
        public array|null $translations,
    ) {
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tag' => $this->tag,
            'type' => $this->type,
            'description' => $this->description,
            'attributes' => $this->attributes,
            'parameters' => $this->parameters,
            'position' => $this->position,
            'enabled' => $this->enabled,
            'placeholder' => $this->placeholder,
            'default_value' => $this->defaultValue,
            'style' => $this->style,
            'options' => array_map(
                static fn ($o) => $o instanceof \JsonSerializable ? $o->jsonSerialize() : $o,
                $this->options,
            ),
            'translations' => $this->translations,
        ];
    }
}
