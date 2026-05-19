<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class PinFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'pin';
    }

    #[\Override]
    public function getDefaultParams(): array
    {
        return [
            'size' => 'md',
            'variant' => 'outline',
            'length' => 4,
            'input_type' => 'text',
            'chars_pattern' => null,
        ];
    }

    #[\Override]
    public function formatDefaultValue(mixed $rawValue): mixed
    {
        return null !== $rawValue ? (string) $rawValue : null;
    }
}
