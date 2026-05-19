<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class DuallistFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'duallist';
    }

    #[\Override]
    public function takesOptions(): bool
    {
        return true;
    }

    #[\Override]
    public function getDefaultParams(): array
    {
        return [
            'size' => 'md',
            'searchable' => true,
            'source_label' => null,
            'target_label' => null,
            'show_move_all_buttons' => true,
        ];
    }

    #[\Override]
    public function formatDefaultValue(mixed $rawValue): mixed
    {
        if (\is_array($rawValue)) {
            return $rawValue;
        }

        return null !== $rawValue ? [$rawValue] : [];
    }
}
