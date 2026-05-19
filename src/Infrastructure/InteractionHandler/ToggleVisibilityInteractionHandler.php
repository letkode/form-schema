<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\InteractionHandler;

use Letkode\FormSchema\Attribute\AsInteractionHandler;

#[AsInteractionHandler]
final class ToggleVisibilityInteractionHandler extends AbstractInteractionHandler
{
    #[\Override]
    public static function getName(): string
    {
        return 'toggle_visibility';
    }
}
