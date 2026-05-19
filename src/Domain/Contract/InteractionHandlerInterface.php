<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Contract;

interface InteractionHandlerInterface
{
    public static function getName(): string;

    public function getDefaultParams(): array;
}
