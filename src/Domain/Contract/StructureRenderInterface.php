<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Contract;

interface StructureRenderInterface
{
    public static function getName(): string;

    /**
     * @param array<string,mixed> $parameters
     *
     * @return array<string,mixed>
     */
    public function renderMeta(array $parameters): array;
}
