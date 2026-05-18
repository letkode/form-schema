<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\DTO;

final readonly class FormDTO implements \JsonSerializable
{
    /**
     * @param list<SectionDTO> $sections
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $tag,
        public string $locale,
        public string $defaultLocale,
        public bool $enabled,
        public array $parameters,
        public string $renderType,
        public array $renderMeta,
        public array|null $translations,
        public array $sections,
    ) {
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tag' => $this->tag,
            'locale' => $this->locale,
            'default_locale' => $this->defaultLocale,
            'enabled' => $this->enabled,
            'parameters' => $this->parameters,
            'render' => ['type' => $this->renderType, 'metadata' => $this->renderMeta],
            'translations' => $this->translations,
            'sections' => array_map(static fn (SectionDTO $s) => $s->jsonSerialize(), $this->sections),
        ];
    }
}
