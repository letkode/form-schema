<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait HasTranslationsTrait
{
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public array|null $translations = null {
        set(array|null $value) => $this->translations = $value;
    }

    public function getTranslation(string $locale, string $field): string|null
    {
        return $this->translations[$locale][$field] ?? null;
    }

    public function setTranslation(string $locale, string $field, string|null $value): void
    {
        if (null === $value) {
            unset($this->translations[$locale][$field]);

            return;
        }

        $this->translations[$locale][$field] = $value;
    }
}
