<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class TreeFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'tree';
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
            'selection_mode' => 'checkbox',
            'auto_select_children' => true,
            'expanded_by_default' => false,
        ];
    }
}
