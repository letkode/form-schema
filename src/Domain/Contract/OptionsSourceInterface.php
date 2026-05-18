<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Contract;

use Letkode\FormSchema\Application\DTO\OptionDTO;

interface OptionsSourceInterface
{
    public static function getName(): string;

    /**
     * @param array<string,mixed> $parameters
     *
     * @return list<OptionDTO>
     */
    public function resolve(array $parameters, string|null $locale = null): array;
}
