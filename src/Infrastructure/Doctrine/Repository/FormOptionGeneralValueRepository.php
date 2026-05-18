<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Letkode\FormSchema\Domain\Entity\FormOptionGeneralValue;

/**
 * @extends ServiceEntityRepository<FormOptionGeneralValue>
 */
final class FormOptionGeneralValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormOptionGeneralValue::class);
    }

    public function save(FormOptionGeneralValue $value, bool $flush = false): void
    {
        $this->getEntityManager()->persist($value);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormOptionGeneralValue $value, bool $flush = false): void
    {
        $this->getEntityManager()->remove($value);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
