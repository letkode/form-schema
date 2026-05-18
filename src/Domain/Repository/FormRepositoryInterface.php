<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Repository;

use Letkode\FormSchema\Domain\Entity\Form;

interface FormRepositoryInterface
{
    public function findOneByTag(string $tag): Form|null;

    public function findById(int $id): Form|null;

    public function save(Form $form, bool $flush = false): void;

    public function remove(Form $form, bool $flush = false): void;
}
