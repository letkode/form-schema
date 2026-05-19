<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\InteractionHandler;

use Letkode\FormSchema\Attribute\AsInteractionHandler;

#[AsInteractionHandler]
final class SetDateConstraintInteractionHandler extends AbstractInteractionHandler
{
    #[\Override]
    public static function getName(): string
    {
        return 'set_date_constraint';
    }

    #[\Override]
    public function getDefaultParams(): array
    {
        return [
            'constraint' => 'min',
        ];
    }
}
