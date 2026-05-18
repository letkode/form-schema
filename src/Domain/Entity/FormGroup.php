<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Letkode\FormSchema\Infrastructure\Doctrine\Repository\FormGroupRepository;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasParametersTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTimestampsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTranslationsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasUuidTrait;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: FormGroupRepository::class)]
#[ORM\Table(name: 'form_group')]
#[ORM\UniqueConstraint(name: 'uniq_form_group_tag', columns: ['section_id', 'tag'])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class FormGroup
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
            $locale = $this->activeLocale ?? $this->section?->form?->defaultLang ?? 'es';

            return $this->getTranslation($locale, 'name')
                ?? $this->getTranslation($this->section?->form?->defaultLang ?? 'es', 'name')
                ?? $this->rawName;
        }
        set(string $value) => $this->rawName = trim($value);
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private string|null $rawDescription = null;

    public string|null $description {
        get {
            $locale = $this->activeLocale ?? $this->section?->form?->defaultLang ?? 'es';

            return $this->getTranslation($locale, 'description')
                ?? $this->getTranslation($this->section?->form?->defaultLang ?? 'es', 'description')
                ?? $this->rawDescription;
        }
        set(?string $value) => $this->rawDescription = null !== $value ? trim($value) : null;
    }

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public int $position = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $enabled = true;

    #[ORM\ManyToOne(targetEntity: FormSection::class, inversedBy: 'groups')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) FormSection|null $section = null;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: FormField::class, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public private(set) Collection $fields;

    private string|null $activeLocale = null;

    public function __construct()
    {
        $this->uuid = new UuidV7();
        $this->fields = new ArrayCollection();
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function setSection(FormSection $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function withActiveLocale(string $locale): static
    {
        $this->activeLocale = $locale;
        foreach ($this->fields as $field) {
            $field->withActiveLocale($locale);
        }

        return $this;
    }

    public function addField(FormField $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setGroup($this);
        }

        return $this;
    }
}
