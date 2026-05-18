<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Contract;

use Letkode\FormSchema\Application\DTO\FormDTO;

interface FormSchemaResolverInterface
{
    public function schema(string $tag): static;

    public function withLocale(string $locale): static;

    public function withContext(string $action): static;

    public function includingSections(array $tags): static;

    public function excludingSections(array $tags): static;

    public function includingGroups(array $tags): static;

    public function excludingGroups(array $tags): static;

    public function resolve(): FormDTO;

    public function toArray(): array;
}
