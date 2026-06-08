<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Enum;

enum FieldTypeEnum: string
{
    case String = 'string';
    case Email = 'email';
    case Phone = 'phone';
    case Number = 'number';
    case Password = 'password';
    case Hidden = 'hidden';
    case Textarea = 'textarea';
    case Date = 'date';
    case DateTime = 'datetime';
    case DateRange = 'date-range';
    case File = 'file';
    case Switch = 'switch';
    case Rating = 'rating';
    case Select = 'select';
    case SelectMultiple = 'select-multiple';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Range = 'range';
    case Combobox = 'combobox';
    case Pin = 'pin';
    case Tree = 'tree';
    case Duallist = 'duallist';
}
