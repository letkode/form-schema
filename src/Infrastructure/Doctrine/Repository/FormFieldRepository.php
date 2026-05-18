<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Letkode\FormSchema\Domain\Entity\FormField;
use Letkode\FormSchema\Domain\Repository\FormFieldRepositoryInterface;

/**
 * @extends ServiceEntityRepository<FormField>
 */
final class FormFieldRepository extends ServiceEntityRepository implements FormFieldRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormField::class);
    }

    #[\Override]
    public function findById(int $id): FormField|null
    {
        return $this->find($id);
    }

    #[\Override]
    public function save(FormField $field, bool $flush = false): void
    {
        $this->getEntityManager()->persist($field);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    #[\Override]
    public function remove(FormField $field, bool $flush = false): void
    {
        $this->getEntityManager()->remove($field);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
