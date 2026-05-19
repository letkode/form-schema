<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\InteractionHandler;

use Letkode\FormSchema\Domain\Contract\InteractionHandlerInterface;

abstract class AbstractInteractionHandler implements InteractionHandlerInterface
{
    #[\Override]
    public function getDefaultParams(): array
    {
        return [];
    }
}
