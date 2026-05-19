<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsInteractionHandler
{
    public function __construct(public int $priority = 0)
    {
    }
}
