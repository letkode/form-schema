<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Exception;

final class FormNotFoundException extends \RuntimeException
{
    public function __construct(string $tag)
    {
        parent::__construct(\sprintf('Form with tag "%s" not found.', $tag));
    }
}
