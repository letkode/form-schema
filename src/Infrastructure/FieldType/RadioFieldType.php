<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class RadioFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'radio';
    }

    #[\Override]
    public function takesOptions(): bool
    {
        return true;
    }
}
