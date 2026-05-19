<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\InteractionHandler;

use Letkode\FormSchema\Attribute\AsInteractionHandler;

#[AsInteractionHandler]
final class AjaxValidateInteractionHandler extends AbstractInteractionHandler
{
    #[\Override]
    public static function getName(): string
    {
        return 'ajax_validate';
    }

    #[\Override]
    public function getDefaultParams(): array
    {
        return [
            'method' => 'GET',
            'debounce' => 0,
        ];
    }
}
