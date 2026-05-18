<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Letkode\FormSchema\Domain\Entity\FormGroup;

/**
 * @extends ServiceEntityRepository<FormGroup>
 */
final class FormGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormGroup::class);
    }

    public function save(FormGroup $group, bool $flush = false): void
    {
        $this->getEntityManager()->persist($group);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormGroup $group, bool $flush = false): void
    {
        $this->getEntityManager()->remove($group);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
