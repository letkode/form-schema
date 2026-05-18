<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\Resolver;

use Letkode\FormSchema\Application\DTO\OptionDTO;
use Letkode\FormSchema\Infrastructure\Registry\OptionsSourceRegistry;

final class OptionsResolver
{
    public function __construct(
        private readonly OptionsSourceRegistry $registry,
    ) {
    }

    /** @return list<OptionDTO> */
    public function resolve(array $setOptions, string|null $locale = null): array
    {
        if (empty($setOptions) || !isset($setOptions['type'])) {
            return [];
        }

        $source = $this->registry->get($setOptions['type']);

        return $source->resolve($setOptions['params'] ?? [], $locale);
    }
}
