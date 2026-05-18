<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Letkode\FormSchema\Infrastructure\Doctrine\Repository\FormSectionRepository;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasParametersTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTimestampsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTranslationsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasUuidTrait;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: FormSectionRepository::class)]
#[ORM\Table(name: 'form_section')]
#[ORM\UniqueConstraint(name: 'uniq_form_section_tag', columns: ['form_id', 'tag'])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class FormSection
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
            $locale = $this->activeLocale ?? $this->form?->defaultLang ?? 'es';

            return $this->getTranslation($locale, 'name')
                ?? $this->getTranslation($this->form?->defaultLang ?? 'es', 'name')
                ?? $this->rawName;
        }
        set(string $value) => $this->rawName = trim($value);
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private string|null $rawDescription = null;

    public string|null $description {
        get {
            $locale = $this->activeLocale ?? $this->form?->defaultLang ?? 'es';

            return $this->getTranslation($locale, 'description')
                ?? $this->getTranslation($this->form?->defaultLang ?? 'es', 'description')
                ?? $this->rawDescription;
        }
        set(?string $value) => $this->rawDescription = null !== $value ? trim($value) : null;
    }

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public int $position = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $enabled = true;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Form|null $form = null;

    #[ORM\OneToMany(mappedBy: 'section', targetEntity: FormGroup::class, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public private(set) Collection $groups;

    private string|null $activeLocale = null;

    public function __construct()
    {
        $this->uuid = new UuidV7();
        $this->groups = new ArrayCollection();
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

    public function setForm(Form $form): static
    {
        $this->form = $form;

        return $this;
    }

    public function withActiveLocale(string $locale): static
    {
        $this->activeLocale = $locale;
        foreach ($this->groups as $group) {
            $group->withActiveLocale($locale);
        }

        return $this;
    }

    public function addGroup(FormGroup $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->setSection($this);
        }

        return $this;
    }
}
