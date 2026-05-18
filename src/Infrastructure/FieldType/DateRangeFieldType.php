<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;

#[AsFieldType]
final class DateRangeFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string
    {
        return 'date-range';
    }

    #[\Override]
    public function formatDefaultValue(mixed $rawValue): mixed
    {
        if (null === $rawValue) {
            return ['from' => null, 'to' => null];
        }

        if (\is_array($rawValue)) {
            return [
                'from' => $rawValue['from'] ?? null,
                'to' => $rawValue['to'] ?? null,
            ];
        }

        return ['from' => null, 'to' => null];
    }
}
