<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\InteractionHandler;

use Letkode\FormSchema\Attribute\AsInteractionHandler;

#[AsInteractionHandler]
final class FilterOptionsInteractionHandler extends AbstractInteractionHandler
{
    #[\Override]
    public static function getName(): string
    {
        return 'filter_options';
    }

    #[\Override]
    public function getDefaultParams(): array
    {
        return [
            'mode' => 'server',
        ];
    }
}
