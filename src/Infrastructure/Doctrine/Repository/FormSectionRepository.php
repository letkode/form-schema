<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Letkode\FormSchema\Domain\Entity\FormSection;

/**
 * @extends ServiceEntityRepository<FormSection>
 */
final class FormSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormSection::class);
    }

    public function save(FormSection $section, bool $flush = false): void
    {
        $this->getEntityManager()->persist($section);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormSection $section, bool $flush = false): void
    {
        $this->getEntityManager()->remove($section);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
