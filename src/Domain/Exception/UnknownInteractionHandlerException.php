<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Exception;

final class UnknownInteractionHandlerException extends \RuntimeException
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf('Unknown interaction handler "%s".', $name));
    }
}
