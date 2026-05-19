<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\ValueObject;

final readonly class FieldInteraction
{
    public function __construct(
        public string $trigger,
        public string $action,
        public string|array|null $target,
        public array $condition,
        public array $params,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            trigger: $data['trigger'],
            action: $data['action'],
            target: $data['target'] ?? null,
            condition: $data['condition'] ?? [],
            params: $data['params'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'trigger' => $this->trigger,
            'action' => $this->action,
            'target' => $this->target,
            'condition' => $this->condition,
            'params' => $this->params,
        ];
    }
}
