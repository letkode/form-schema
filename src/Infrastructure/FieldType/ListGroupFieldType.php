<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class ListGroupFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'list-group';
    }

    #[\Override]
    public function takesOptions(): bool
    {
        return true;
    }
}
