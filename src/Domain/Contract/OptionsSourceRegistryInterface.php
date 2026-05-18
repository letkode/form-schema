<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Contract;

interface OptionsSourceRegistryInterface
{
    public function get(string $name): OptionsSourceInterface;

    public function has(string $name): bool;

    /** @return array<string, OptionsSourceInterface> */
    public function all(): array;
}
