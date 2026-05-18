<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Repository;

use Letkode\FormSchema\Domain\Entity\FormField;

interface FormFieldRepositoryInterface
{
    public function findById(int $id): FormField|null;

    public function save(FormField $field, bool $flush = false): void;

    public function remove(FormField $field, bool $flush = false): void;
}
