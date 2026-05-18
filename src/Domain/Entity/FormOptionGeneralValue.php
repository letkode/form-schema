<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Letkode\FormSchema\Infrastructure\Doctrine\Repository\FormOptionGeneralValueRepository;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasParametersTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTimestampsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTranslationsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasUuidTrait;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: FormOptionGeneralValueRepository::class)]
#[ORM\Table(name: 'form_option_general_value')]
#[ORM\UniqueConstraint(name: 'uniq_form_option_general_value_tag', columns: ['group_id', 'tag'])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class FormOptionGeneralValue
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

    #[ORM\Column(type: Types::STRING, length: 100)]
    public private(set) string $tag;

    #[ORM\Column(type: Types::STRING)]
    private string $rawText;

    public string $text {
        get => $this->rawText;
        set(string $value) => $this->rawText = trim($value);
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private string|null $rawDescription = null;

    public string|null $description {
        get => $this->rawDescription;
        set(?string $value) => $this->rawDescription = null !== $value ? trim($value) : null;
    }

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public int $position = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $enabled = true;

    #[ORM\ManyToOne(targetEntity: FormOptionGeneral::class, inversedBy: 'values')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) FormOptionGeneral|null $group = null;

    public function __construct()
    {
        $this->uuid = new UuidV7();
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function setGroup(FormOptionGeneral $group): static
    {
        $this->group = $group;

        return $this;
    }
}
