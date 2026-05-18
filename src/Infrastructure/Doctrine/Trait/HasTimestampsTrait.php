<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Trait;

use Doctrine\ORM\Mapping as ORM;

trait HasTimestampsTrait
{
    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column]
    public private(set) \DateTimeImmutable $updatedAt;

    #[ORM\PrePersist]
    public function initTimestamps(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
