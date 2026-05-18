<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Exception;

final class UnauthorizedOptionsMethodException extends \RuntimeException
{
    public function __construct(string $class, string $method)
    {
        parent::__construct(\sprintf(
            'Method "%s::%s" is not authorized as a form options provider. Use #[AsFormOptionsProvider] to register it.',
            $class,
            $method,
        ));
    }
}
