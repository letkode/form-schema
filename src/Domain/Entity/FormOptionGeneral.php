<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Letkode\FormSchema\Infrastructure\Doctrine\Repository\FormOptionGeneralRepository;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasParametersTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTimestampsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTranslationsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasUuidTrait;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: FormOptionGeneralRepository::class)]
#[ORM\Table(name: 'form_option_general')]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class FormOptionGeneral
{
    use HasParametersTrait;
    use HasTimestampsTrait;
    use HasTranslationsTrait;
    use HasUuidTrait;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) int|null $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    public private(set) string $tag;

    #[ORM\Column(type: Types::STRING)]
    private string $rawName;

    public string $name {
        get => $this->rawName;
        set(string $value) => $this->rawName = trim($value);
    }

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: FormOptionGeneralValue::class, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public private(set) Collection $values;

    public function __construct()
    {
        $this->uuid = new UuidV7();
        $this->values = new ArrayCollection();
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function addValue(FormOptionGeneralValue $value): static
    {
        if (!$this->values->contains($value)) {
            $this->values->add($value);
            $value->setGroup($this);
        }

        return $this;
    }
}
