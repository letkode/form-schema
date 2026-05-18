<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\DTO;

final readonly class OptionGroupDTO implements \JsonSerializable
{
    /**
     * @param list<OptionDTO> $options
     */
    public function __construct(
        public string $text,
        public array $options,
    ) {
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'text' => $this->text,
            'options' => array_map(static fn (OptionDTO $o) => $o->jsonSerialize(), $this->options),
        ];
    }
}
