<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class SelectMultipleFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'select-multiple';
    }

    #[\Override]
    public function takesOptions(): bool
    {
        return true;
    }

    #[\Override]
    public function formatDefaultValue(mixed $rawValue): mixed
    {
        if (\is_array($rawValue)) {
            return $rawValue;
        }

        return null !== $rawValue ? [$rawValue] : [];
    }

    #[\Override]
    public function getDefaultParams(): array
    {
        return [
            'size' => 'md',
            'searchable' => false,
            'tags_mode' => false,
            'search_limit' => null,
            'min_search_length' => null,
            'max_count_items' => null,
        ];
    }
}
