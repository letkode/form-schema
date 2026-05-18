<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait HasParametersTrait
{
    #[ORM\Column(type: Types::JSON)]
    public array $parameters = [];

    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function setParameter(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }
}
