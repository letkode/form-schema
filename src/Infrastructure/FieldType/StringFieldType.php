<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class StringFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'string';
    }
}
