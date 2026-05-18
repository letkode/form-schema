<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Letkode\FormSchema\Infrastructure\Doctrine\Repository\FormRepository;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasParametersTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTimestampsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasTranslationsTrait;
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\HasUuidTrait;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: FormRepository::class)]
#[ORM\Table(name: 'form')]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Form
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
        get {
            $locale = $this->activeLocale ?? $this->defaultLang;

            return $this->getTranslation($locale, 'name')
                ?? $this->getTranslation($this->defaultLang, 'name')
                ?? $this->rawName;
        }
        set(string $value) => $this->rawName = trim($value);
    }

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $enabled = true;

    #[ORM\Column(type: Types::STRING, length: 5, options: ['default' => 'es'])]
    public string $defaultLang = 'es';

    #[ORM\OneToMany(mappedBy: 'form', targetEntity: FormSection::class, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public private(set) Collection $sections;

    private string|null $activeLocale = null;

    public function __construct()
    {
        $this->uuid = new UuidV7();
        $this->sections = new ArrayCollection();
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function withActiveLocale(string $locale): static
    {
        $this->activeLocale = $locale;
        foreach ($this->sections as $section) {
            $section->withActiveLocale($locale);
        }

        return $this;
    }

    public function addSection(FormSection $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setForm($this);
        }

        return $this;
    }
}
