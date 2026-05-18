<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Exception;

final class UnknownRenderException extends \RuntimeException
{
    public function __construct(string $level, string $name)
    {
        parent::__construct(\sprintf('Unknown %s render "%s".', $level, $name));
    }
}
