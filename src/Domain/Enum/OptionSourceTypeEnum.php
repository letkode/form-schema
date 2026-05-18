<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Enum;

enum OptionSourceTypeEnum: string
{
    case General = 'general';
    case Entity = 'entity';
}
