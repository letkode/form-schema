<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\FormRender;

use Letkode\FormSchema\Attribute\AsFormRender;
use Letkode\FormSchema\Domain\Contract\FormRenderInterface;

#[AsFormRender]
final class ModalFormRender implements FormRenderInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'modal';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'size'              => $parameters['modal']['size'] ?? 'md',
            'position'          => $parameters['modal']['position'] ?? 'center',
            'dismissible'       => $parameters['modal']['dismissible'] ?? true,
            'show_close_button' => $parameters['modal']['show_close_button'] ?? true,
        ];
    }
}
