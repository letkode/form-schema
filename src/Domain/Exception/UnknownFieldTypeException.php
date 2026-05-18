<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Exception;

final class UnknownFieldTypeException extends \RuntimeException
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf('Unknown field type "%s".', $name));
    }
}
