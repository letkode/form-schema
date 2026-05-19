<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Contract;

interface InteractionHandlerRegistryInterface
{
    public function get(string $name): InteractionHandlerInterface;

    public function has(string $name): bool;

    /** @return array<string, InteractionHandlerInterface> */
    public function all(): array;
}
