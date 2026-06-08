<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FormRender;

use Letkode\FormSchema\Attribute\AsFormRender;
use Letkode\FormSchema\Domain\Contract\FormRenderInterface;

#[AsFormRender]
final class StepperFormRender implements FormRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'stepper';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'orientation' => $parameters['stepper']['orientation'] ?? 'horizontal',
            'show_progress' => $parameters['stepper']['show_progress'] ?? true,
            'allow_skip' => $parameters['stepper']['allow_skip'] ?? false,
            'persist_on_navigate' => $parameters['stepper']['persist_on_navigate'] ?? true,
        ];
    }
}
