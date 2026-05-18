<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Domain\Contract\FieldTypeInterface;
use Letkode\FormSchema\Domain\ValueObject\FieldAttributes;

abstract class AbstractFieldType implements FieldTypeInterface
{
    #[\Override]
    public function takesOptions(): bool
    {
        return false;
    }

    #[\Override]
    public function getDefaultAttributes(): FieldAttributes
    {
        return FieldAttributes::default();
    }

    #[\Override]
    public function formatDefaultValue(mixed $rawValue): mixed
    {
        return $rawValue;
    }
}
