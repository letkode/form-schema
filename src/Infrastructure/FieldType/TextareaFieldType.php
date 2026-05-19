<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class TextareaFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'textarea';
    }

    #[\Override]
    public function getDefaultParams(): array
    {
        return [
            'size' => 'md',
            'label_style' => 'default',
            'icon_leading' => null,
            'icon_trailing' => null,
        ];
    }
}
