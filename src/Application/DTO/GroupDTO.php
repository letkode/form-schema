<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\DTO;

final readonly class GroupDTO implements \JsonSerializable
{
    /**
     * @param list<FieldDTO> $fields
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $tag,
        public string|null $description,
        public int $position,
        public bool $enabled,
        public array $parameters,
        public string $renderType,
        public array $renderMeta,
        public array|null $translations,
        public array $fields,
    ) {
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tag' => $this->tag,
            'description' => $this->description,
            'position' => $this->position,
            'enabled' => $this->enabled,
            'parameters' => $this->parameters,
            'render' => ['type' => $this->renderType, 'metadata' => $this->renderMeta],
            'translations' => $this->translations,
            'fields' => array_map(static fn (FieldDTO $f) => $f->jsonSerialize(), $this->fields),
        ];
    }
}
