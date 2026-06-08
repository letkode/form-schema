<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\DTO;

final readonly class OptionDTO implements \JsonSerializable
{
    public function __construct(
        public string|int $value,
        public string $label,
        public string|null $tag = null,
        public string|null $icon = null,
        public string|null $color = null,
        public int $position = 0,
        public array $data = [],
    ) {
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'tag' => $this->tag,
            'icon' => $this->icon,
            'color' => $this->color,
            'position' => $this->position,
            'data' => $this->data,
        ];
    }
}
