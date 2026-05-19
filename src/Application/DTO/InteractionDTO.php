<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\DTO;

use Letkode\FormSchema\Domain\ValueObject\FieldInteraction;

final readonly class InteractionDTO implements \JsonSerializable
{
    public function __construct(
        public string $trigger,
        public string $action,
        public string|array|null $target,
        public array $condition,
        public array $params,
    ) {
    }

    public static function fromInteraction(FieldInteraction $interaction, array|null $mergedParams = null): self
    {
        return new self(
            trigger: $interaction->trigger,
            action: $interaction->action,
            target: $interaction->target,
            condition: $interaction->condition,
            params: $mergedParams ?? $interaction->params,
        );
    }

    #[\Override]
    public function jsonSerialize(): array
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
