<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

trait HasUuidTrait
{
    #[ORM\Column(type: 'uuid', unique: true)]
    public private(set) Uuid $uuid;

    private function initUuid(): void
    {
        $this->uuid = new UuidV7();
    }
}
