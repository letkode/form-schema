<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Letkode\FormSchema\Infrastructure\Doctrine\Repository\FormFieldRepository;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasParametersTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTimestampsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTranslationsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasUuidTrait;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: FormFieldRepository::class)]
#[ORM\Table(name: 'form_field')]
#[ORM\UniqueConstraint(name: 'uniq_form_field_tag', columns: ['group_id', 'tag'])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class FormField
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
    private string $rawName;

    public string $name {
        get {
            $locale = $this->activeLocale ?? $this->group?->section?->form?->defaultLang ?? 'es';

            return $this->getTranslation($locale, 'name')
                ?? $this->getTranslation($this->group?->section?->form?->defaultLang ?? 'es', 'name')
                ?? $this->rawName;
        }
        set(string $value) => $this->rawName = trim($value);
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private string|null $rawDescription = null;

    public string|null $description {
        get {
            $locale = $this->activeLocale ?? $this->group?->section?->form?->defaultLang ?? 'es';

            return $this->getTranslation($locale, 'description')
                ?? $this->getTranslation($this->group?->section?->form?->defaultLang ?? 'es', 'description')
                ?? $this->rawDescription;
        }
        set(?string $value) => $this->rawDescription = null !== $value ? trim($value) : null;
    }

    #[ORM\Column(type: Types::STRING, length: 50)]
    public string $type;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public int $position = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $enabled = true;

    #[ORM\Column(type: Types::JSON)]
    public array $attributes = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    public array|null $interactions = null;

    #[ORM\ManyToOne(targetEntity: FormGroup::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) FormGroup|null $group = null;

    private string|null $activeLocale = null;

    public function __construct()
    {
        $this->uuid = new UuidV7();
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function setGroup(FormGroup $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function withActiveLocale(string $locale): static
    {
        $this->activeLocale = $locale;

        return $this;
    }
}
