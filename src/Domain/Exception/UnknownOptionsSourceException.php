<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Exception;

final class UnknownOptionsSourceException extends \RuntimeException
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf('Unknown options source "%s".', $name));
    }
}
