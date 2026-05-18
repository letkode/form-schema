<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class AsFormOptionsProvider
{
    /** @var list<string> */
    public array $methods;

    public function __construct(string|array $method = 'findForFormOptions')
    {
        $this->methods = (array) $method;
    }
}
