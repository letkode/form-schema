<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Letkode\FormSchema\Domain\Entity\FormOptionGeneral;
use Letkode\FormSchema\Domain\Repository\FormOptionGeneralRepositoryInterface;

/**
 * @extends ServiceEntityRepository<FormOptionGeneral>
 */
final class FormOptionGeneralRepository extends ServiceEntityRepository implements FormOptionGeneralRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormOptionGeneral::class);
    }

    #[\Override]
    public function findOneByTag(string $tag): FormOptionGeneral|null
    {
        return $this->findOneBy(['tag' => $tag]);
    }

    #[\Override]
    public function findById(int $id): FormOptionGeneral|null
    {
        return $this->find($id);
    }

    #[\Override]
    public function save(FormOptionGeneral $optionGeneral, bool $flush = false): void
    {
        $this->getEntityManager()->persist($optionGeneral);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    #[\Override]
    public function remove(FormOptionGeneral $optionGeneral, bool $flush = false): void
    {
        $this->getEntityManager()->remove($optionGeneral);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
