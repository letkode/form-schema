<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FormRender;

use Letkode\FormSchema\Attribute\AsFormRender;
use Letkode\FormSchema\Domain\Contract\FormRenderInterface;

#[AsFormRender]
final class WizardFormRender implements FormRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'wizard';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'navigation' => 'horizontal',
            'show_progress' => true,
            'enforce_completion' => true,
            'allow_skip' => false,
        ];
    }
}
