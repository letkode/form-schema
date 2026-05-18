<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class DateFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'date';
    }

    #[\Override]
    public function formatDefaultValue(mixed $rawValue): mixed
    {
        if (null === $rawValue || '' === $rawValue) {
            return null;
        }

        if (\is_string($rawValue) && (str_starts_with($rawValue, '+') || str_starts_with($rawValue, '-'))) {
            $date = new \DateTimeImmutable()->modify($rawValue);

            return false !== $date ? $date->format('Y-m-d') : $rawValue;
        }

        return $rawValue;
    }
}
