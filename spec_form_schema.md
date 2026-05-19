# Spec-Driven: `letkode/form-schema`

> Symfony Bundle (PHP 8.4) para sistemas de formularios dinámicos configurables desde base de datos.

---

## 1. Decisiones confirmadas

| Decisión | Valor |
|---|---|
| Lenguaje | PHP 8.4 (uso intensivo de property hooks, asymmetric visibility, `new` chaining, `array_find` / `array_any`, lazy objects nativos) |
| Framework target | Symfony Bundle 7.x (LTS 7.4) |
| ORM | Doctrine ORM **^3.4** (incluye soporte oficial de property hooks + lazy objects nativos PHP 8.4) |
| BD soportada | Cualquier driver de Doctrine; optimizado para PostgreSQL (`Types::JSON` resuelve a `jsonb` en PG y `json` en MySQL/MariaDB) |
| Vendor / nombre | `letkode/form-schema` |
| Namespace raíz | `Letkode\FormSchema\` |
| Alcance | Solo lectura del schema. Entidades + repositorios + ValueObjects públicos para que el proyecto consumidor cree formularios programáticamente. Sin admin UI ni controladores REST built-in. |
| Versionado de formularios | No. Último siempre gana. |
| Cache | PSR-6 opt-in, desactivado por defecto, invalidable por `tag` |
| Soft delete | Sí, en **todas** las entidades (`Form`, `FormSection`, `FormGroup`, `FormField`, `FormOptionGeneral`, `FormOptionGeneralValue`) |
| Locale | Dinámico por request vía `->withLocale($locale)`. Fallback: `locale solicitado → Form.default_lang → valor crudo del campo` |
| Validación server-side | Fuera de v1 (la estructura es la prioridad) |
| `group_render` | Mini-registry igual que FieldTypes: built-in (`simple`, `matrix`, `tabs`) + registrables desde el proyecto |
| Método default `OptionsEntitySource` | `findForFormOptions(array $context): array` |
| Dependencia interna | `letkode/helpers` (validators, conversores, slugify) — reutilizamos en lugar de reimplementar |
| Bundle skeleton | `Symfony\Component\HttpKernel\Bundle\AbstractBundle` (Symfony 7 style, igual que `letkode/helpers`) |

---

## 2. Aprovechamiento de PHP 8.4

El paquete está pensado **nativamente para PHP 8.4**. No es un port retrocompatible: cada feature relevante de 8.4 se usa donde aporta valor real.

### 2.1 Asymmetric visibility (`public private(set)`)

Usada en todas las entidades para campos que deben ser **públicamente legibles pero solo escribibles internamente** (id, uuid, tag, timestamps). Es 100% compatible con Doctrine porque la hidratación va por reflexión y bypasea visibility guards.

```php
#[ORM\Entity]
class Form
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, unique: true)]
    public private(set) string $tag;

    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt = new \DateTimeImmutable();
}
```

Beneficio: elimina la mitad de los `getId()` / `getUuid()` / `getCreatedAt()` sin perder encapsulamiento del setter. El proyecto puede hacer `$form->id` o `$form->tag` directamente, sin métodos.

### 2.2 Property hooks

Usados para **propiedades virtuales o derivadas** (que NO se mapean a columnas), especialmente para resolver el valor traducido sin escribir `getName()`.

```php
class Form
{
    #[ORM\Column(type: Types::STRING)]
    private string $rawName;                                    // columna ORM

    /**
     * Property hook virtual: devuelve el nombre en el locale activo.
     * NO se mapea (no tiene #[ORM\Column]).
     */
    public string $name {
        get => $this->translations[$this->activeLocale]['name'] ?? $this->rawName;
        set (string $value) => $this->rawName = trim($value);
    }

    public private(set) string $activeLocale = 'es';

    public function withLocale(string $locale): static
    {
        $this->activeLocale = $locale;
        return $this;
    }
}
```

> **Restricción de Doctrine ORM 3.4**: una propiedad con hook **virtual** (sin backing field) no puede mapearse a una columna. Por eso el patrón es: backing field privado mapeado + property hook público para lectura. Doctrine se encarga del backing field y el hook sirve la capa de traducción/transformación.

### 2.3 `new` en initializers y chained access

Permite defaults expresivos en constructores y encadenar inmediatamente sobre `new`:

```php
public function __construct(
    public private(set) \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    public private(set) Uuid $uuid = new UuidV7(),
) {}

// Chained access (sin paréntesis extra):
$dto = new FieldDTO(/*...*/)->withExtra('rendered_at', new \DateTimeImmutable());
```

### 2.4 Nuevas funciones de array: `array_find`, `array_find_key`, `array_any`, `array_all`

Reemplazan loops `foreach + if + break` en el resolver y los Registries. Ejemplos en el código del paquete:

```php
// FieldTypeRegistry: existe algún tipo que tome opciones?
public function hasAnyOptionsType(): bool
{
    return array_any($this->types, static fn($t) => $t->takesOptions());
}

// Resolver: encontrar el primer field requerido sin valor
$missing = array_find(
    $section->fields,
    static fn(FieldDTO $f) => $f->attributes['required'] && $f->defaultValue === null,
);

// StructureFilter: validar que todos los tags pedidos existen
$allExist = array_all($requestedTags, fn($tag) => $this->repo->exists($tag));
```

### 2.5 `#[\Override]` (introducido en 8.3, obligatorio en este paquete)

Aplicado en **toda** sobrescritura. Lo declaramos como regla del code-style.

```php
final class StringFieldType extends AbstractFieldType
{
    #[\Override]
    public static function getName(): string { return 'string'; }
}
```

### 2.6 `#[\Deprecated]`

Usado para evolución de API entre versiones menores sin romper consumidores. Ej. si en 1.2 reemplazamos `getName()` por el property hook `name`:

```php
#[\Deprecated(message: 'Use $form->name property directly', since: '1.2.0')]
public function getName(): string { return $this->name; }
```

### 2.7 Lazy objects nativos (vía Doctrine 3.4)

No requieren código adicional. Doctrine ORM 3.4 usa `Reflector::newLazyGhost()` internamente para proxies. El paquete se beneficia automáticamente:
- Cargar un `Form` no instancia inmediatamente sus `Sections`, `Groups` y `Fields` — solo cuando se accede a la colección.
- Importante en el resolver porque el método `getFieldsArrayCollections()` puede iterar miles de campos en formularios grandes.

### 2.8 Atributos PHP (uso intensivo)

El paquete usa atributos como mecanismo principal de configuración/extensión (no anotaciones, no YAML). Auto-discovery vía `registerForAutoconfiguration` + `#[AutowireIterator]` en los registries (ver 2.8.3).

#### 2.8.1 Atributos propios del paquete

| Atributo | Aplica a | Efecto |
|---|---|---|
| `#[AsFieldType]` | Clases que implementan `FieldTypeInterface` | Auto-registro en `FieldTypeRegistry` |
| `#[AsOptionsSource]` | Clases que implementan `OptionsSourceInterface` | Auto-registro en `OptionsSourceRegistry` |
| `#[AsFormRender]` | Clases que implementan `FormRenderInterface` | Auto-registro en `FormRenderRegistry` |
| `#[AsSectionRender]` | Clases que implementan `SectionRenderInterface` | Auto-registro en `SectionRenderRegistry` |
| `#[AsGroupRender]` | Clases que implementan `GroupRenderInterface` | Auto-registro en `GroupRenderRegistry` |
| `#[AsInteractionHandler]` | Clases que implementan `InteractionHandlerInterface` | Auto-registro en `InteractionHandlerRegistry` |

Todos los atributos del paquete admiten parámetro opcional `priority: int` para resolver colisiones cuando hay overrides.

```php
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsFieldType
{
    public function __construct(public int $priority = 0) {}
}
```

#### 2.8.2 Atributos de Symfony reutilizados

Cuando un atributo nativo de Symfony resuelve el problema, **se usa ese en lugar de inventar uno propio**. Los más relevantes en este paquete:

| Atributo Symfony | Dónde se usa en el paquete | Para qué |
|---|---|---|
| `#[Autowire]` | Inyección de parámetros específicos del contenedor | Ej. inyectar `%letkode_form_schema.entity_namespace%` en `OptionsEntitySource`. |
| `#[AutowireIterator]` | Registries que reciben todos los servicios de un tag | Reemplaza compiler passes en muchos casos (ver 2.8.3). |
| `#[AutowireLocator]` | Acceso lazy a servicios por nombre | Para resolver `FieldType` por nombre sin instanciar los 18 al arranque. |
| `#[AsTaggedItem]` | Servicios tag-eados que necesitan `index` o `priority` declarativos | Útil si un `FieldType` custom del proyecto necesita prioridad alta. |
| `#[AsEventListener]` | `DoctrineCacheInvalidationSubscriber` | Escucha eventos `postUpdate`/`postPersist`/`postRemove` de las entidades del paquete. |
| `#[When]` | Definiciones condicionales por entorno | Ej. desactivar cache en `test`. |
| `#[Required]` | Setter injection en clases abstractas | En `AbstractFieldType` si necesitamos inyectar el `TranslatorInterface` opcional sin romper subclases. |
| `#[Target]` | Desambiguar inyecciones con interfaces múltiples | Ej. múltiples implementaciones de `CacheItemPoolInterface`. |

> **Regla del paquete**: prefiero atributos Symfony sobre atributos propios cuando cumplen el mismo rol. Solo creo atributos nuevos cuando aporto semántica que Symfony no expresa (como `#[AsFieldType]` que comunica intención de dominio, no solo tagging técnico).

#### 2.8.3 Ejemplo: `AutowireIterator` simplifica los registries

Sin atributos Symfony (versión con compiler pass):

```php
final class FieldTypeRegistry
{
    private array $types = [];
    public function register(FieldTypeInterface $type): void { /* ... */ }
}

// + un FieldTypeRegistryPass que recorre los tags y llama register()
```

Con `#[AutowireIterator]` (sin compiler pass):

```php
final class FieldTypeRegistry
{
    private array $types = [];

    public function __construct(
        #[AutowireIterator('form_schema.field_type', defaultPriorityMethod: 'getPriority')]
        iterable $taggedTypes,
    ) {
        foreach ($taggedTypes as $type) {
            $this->types[$type::getName()] = $type;
        }
    }
}
```

Beneficio: menos código, menos archivos, autoconfigure sigue funcionando porque el tag se aplica vía `registerForAutoconfiguration` en el bundle. **El paquete usará este patrón para los 5 registries**, eliminando los 5 compiler passes mencionados en secciones anteriores.

> **Cambio respecto a versiones anteriores del spec**: los `*RegistryPass` listados en la estructura del directorio (5.x) y en el bundle skeleton (16.1) se eliminan. La carpeta `DependencyInjection/Compiler/` queda vacía y se borra. El bundle solo declara los `registerForAutoconfiguration()` y los registries reciben sus servicios vía `#[AutowireIterator]` en el constructor.

### 2.9 Readonly classes para VOs y DTOs

Todas las clases de `Domain/ValueObject/` y `Application/DTO/` se declaran `final readonly`. Esto fuerza inmutabilidad estructural sin escribir manualmente la lógica.

```php
final readonly class FieldAttributes
{
    public function __construct(
        public bool $required = false,
        public bool $readonly = false,
        // ...
        public array $extra = [],
    ) {}
}
```

---

## 3. Objetivo

Proveer una librería instalable vía Composer que permita:

1. Modelar formularios en BD como `Form → Section → Group → Field` con i18n.
2. Resolver el schema de un formulario por su `tag` y devolverlo como DTO/array listo para ser consumido por un frontend.
3. Tener **18 tipos de campo predefinidos** y permitir al proyecto consumidor registrar tipos custom siguiendo el patrón de `Doctrine\DBAL\Types\Type::addType()`.
4. Poblar campos tipo lista desde dos fuentes (`options_general` interna, `options_entity` externa al paquete) y permitir registrar más fuentes desde el proyecto.
5. Permitir extender los atributos y parámetros de cada nivel del schema mediante ValueObjects.

---

## 4. Tipos de campo predefinidos

| # | Tipo | Toma opciones | Notas |
|---|---|---|---|
| 1 | `string` | No | Input single-line |
| 2 | `email` | No | Input + validación email |
| 3 | `phone` | No | Input + máscara/validación teléfono |
| 4 | `number` | No | Input numérico |
| 5 | `password` | No | Input password |
| 6 | `hidden` | No | Input hidden |
| 7 | `textarea` | No | Multi-line |
| 8 | `date` | No | Date picker |
| 9 | `datetime` | No | DateTime picker |
| 10 | `date-range` | No | Rango de fechas (devuelve `['from','to']`) |
| 11 | `file` | No | Upload |
| 12 | `switch` | **Sí** | Toggle on/off (típicamente 2 opciones) |
| 13 | `rating` | **Sí** | Rating con iconos. Las opciones definen ícono y valor |
| 14 | `list` | **Sí** | Single select |
| 15 | `list-group` | **Sí** | Single select con opciones agrupadas |
| 16 | `multilist` | **Sí** | Multi select |
| 17 | `radio` | **Sí** | Radio buttons |
| 18 | `checkbox` | **Sí** | Checkboxes (múltiple) |

> Renombrado de `mood` → `rating` (por convención más estándar).

---

## 5. Estructura del paquete

```
letkode/form-schema/
├── composer.json
├── README.md
├── LICENSE
├── phpunit.xml.dist
├── config/
│   └── packages/
│       └── letkode_form_schema.yaml      # config de ejemplo
├── src/
│   ├── LetkodeFormSchemaBundle.php                # extiende AbstractBundle (Symfony 7)
│   ├── Resources/
│   │   └── config/
│   │       └── services.yaml
│   ├── Migrations/
│   │   └── Version20260101000000.php             # initial schema
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── Form.php
│   │   │   ├── FormSection.php
│   │   │   ├── FormGroup.php
│   │   │   ├── FormField.php
│   │   │   ├── FormOptionGeneral.php
│   │   │   └── FormOptionGeneralValue.php
│   │   ├── Enum/
│   │   │   ├── FieldTypeEnum.php
│   │   │   ├── OptionSourceTypeEnum.php
│   │   │   └── FilterStructureTypeEnum.php
│   │   ├── ValueObject/
│   │   │   ├── FieldAttributes.php
│   │   │   ├── FieldInteraction.php
│   │   │   ├── FieldParameters.php
│   │   │   ├── FormParameters.php
│   │   │   ├── SectionParameters.php
│   │   │   └── GroupParameters.php
│   │   ├── Contract/
│   │   │   ├── FieldTypeInterface.php
│   │   │   ├── OptionsSourceInterface.php
│   │   │   ├── OptionsSourceRegistryInterface.php
│   │   │   ├── StructureRenderInterface.php
│   │   │   ├── FormRenderInterface.php
│   │   │   ├── SectionRenderInterface.php
│   │   │   ├── GroupRenderInterface.php
│   │   │   ├── ExtensibleAttributesInterface.php
│   │   │   ├── ExtensibleParametersInterface.php
│   │   │   ├── HasOptionsInterface.php
│   │   │   ├── ProvidesFormOptionsInterface.php
│   │   │   └── FormSchemaResolverInterface.php
│   │   └── Repository/
│   │       ├── FormRepositoryInterface.php
│   │       ├── FormFieldRepositoryInterface.php
│   │       ├── FormOptionGeneralRepositoryInterface.php
│   │       └── ...
│   ├── Infrastructure/
│   │   ├── Doctrine/
│   │   │   ├── Repository/
│   │   │   │   ├── FormRepository.php
│   │   │   │   ├── FormFieldRepository.php
│   │   │   │   ├── FormOptionGeneralRepository.php
│   │   │   │   └── ...
│   │   │   └── Trait/
│   │   │       ├── HasTranslationsTrait.php
│   │   │       ├── HasUuidTrait.php
│   │   │       ├── HasParametersTrait.php
│   │   │       └── HasTimestampsTrait.php
│   │   ├── FieldType/
│   │   │   ├── AbstractFieldType.php
│   │   │   ├── StringFieldType.php
│   │   │   ├── EmailFieldType.php
│   │   │   ├── PhoneFieldType.php
│   │   │   ├── NumberFieldType.php
│   │   │   ├── PasswordFieldType.php
│   │   │   ├── HiddenFieldType.php
│   │   │   ├── TextareaFieldType.php
│   │   │   ├── DateFieldType.php
│   │   │   ├── DateTimeFieldType.php
│   │   │   ├── DateRangeFieldType.php
│   │   │   ├── FileFieldType.php
│   │   │   ├── SwitchFieldType.php
│   │   │   ├── RatingFieldType.php
│   │   │   ├── ListFieldType.php
│   │   │   ├── ListGroupFieldType.php
│   │   │   ├── MultilistFieldType.php
│   │   │   ├── RadioFieldType.php
│   │   │   └── CheckboxFieldType.php
│   │   ├── OptionsSource/
│   │   │   ├── OptionsGeneralSource.php
│   │   │   └── OptionsEntitySource.php
│   │   ├── FormRender/
│   │   │   ├── SimpleFormRender.php
│   │   │   ├── StepperFormRender.php
│   │   │   ├── WizardFormRender.php
│   │   │   ├── TabsFormRender.php
│   │   │   └── ModalFormRender.php
│   │   ├── SectionRender/
│   │   │   ├── SimpleSectionRender.php
│   │   │   ├── AccordionSectionRender.php
│   │   │   ├── TabsSectionRender.php
│   │   │   └── CollapsibleSectionRender.php
│   │   ├── GroupRender/
│   │   │   ├── SimpleGroupRender.php
│   │   │   ├── MatrixGroupRender.php
│   │   │   └── TabsGroupRender.php
│   │   ├── InteractionHandler/
│   │   │   ├── AbstractInteractionHandler.php
│   │   │   ├── FilterOptionsInteractionHandler.php
│   │   │   ├── ToggleVisibilityInteractionHandler.php
│   │   │   ├── ToggleRequiredInteractionHandler.php
│   │   │   ├── SetValueInteractionHandler.php
│   │   │   ├── AjaxValidateInteractionHandler.php
│   │   │   ├── SetDateConstraintInteractionHandler.php
│   │   │   └── ComputeInteractionHandler.php
│   │   ├── Registry/
│   │   │   ├── FieldTypeRegistry.php
│   │   │   ├── OptionsSourceRegistry.php
│   │   │   ├── FormRenderRegistry.php
│   │   │   ├── SectionRenderRegistry.php
│   │   │   ├── GroupRenderRegistry.php
│   │   │   └── InteractionHandlerRegistry.php
│   │   └── Cache/
│   │       └── CachedFormSchemaResolver.php       # decorator PSR-6
│   ├── Application/
│   │   ├── DTO/
│   │   │   ├── FormDTO.php
│   │   │   ├── SectionDTO.php
│   │   │   ├── GroupDTO.php
│   │   │   ├── FieldDTO.php
│   │   │   ├── OptionDTO.php
│   │   │   ├── OptionGroupDTO.php
│   │   │   └── InteractionDTO.php
│   │   ├── Resolver/
│   │   │   ├── FormSchemaResolver.php             # servicio público principal
│   │   │   ├── OptionsResolver.php
│   │   │   └── AttributesResolver.php
│   │   └── Filter/
│   │       ├── StructureFilter.php
│   │       └── FilterCriteria.php
│   └── Attribute/                                 # PHP attributes para extensión
│       ├── AsFieldType.php
│       ├── AsOptionsSource.php
│       ├── AsFormRender.php
│       ├── AsSectionRender.php
│       ├── AsGroupRender.php
│       └── AsInteractionHandler.php
└── tests/
    ├── Unit/
    └── Integration/
```

---

## 6. Modelo de dominio

### 6.0 Ejemplo de entidad con PHP 8.4 (Form)

Este es el shape esperado de la entidad `Form` aplicando todas las features de 8.4 descritas en la sección 2. El resto de entidades (`FormSection`, `FormGroup`, `FormField`) siguen el mismo patrón.

```php
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
use Letkode\FormSchema\Infrastructure\Doctrine\Trait\{
    HasTimestampsTrait, HasTranslationsTrait, HasUuidTrait, HasParametersTrait
};
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FormRepository::class)]
#[ORM\Table(name: 'form')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Form
{
    use HasTranslationsTrait;
    use HasUuidTrait;
    use HasParametersTrait;
    use HasTimestampsTrait;
    use SoftDeleteableEntity;

    // Asymmetric visibility: lectura pública, escritura interna
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, unique: true)]
    public private(set) string $tag;

    // Backing field privado para el property hook de `$name` traducido
    #[ORM\Column(type: Types::STRING)]
    private string $rawName;

    /**
     * Property hook virtual: devuelve el nombre traducido al locale activo
     * con cadena de fallback. NO se mapea (no tiene #[ORM\Column]).
     */
    public string $name {
        get {
            $locale = $this->activeLocale ?? $this->defaultLang;
            return $this->getTranslation($locale, 'name')
                ?? $this->getTranslation($this->defaultLang, 'name')
                ?? $this->rawName;
        }
        set (string $value) => $this->rawName = trim($value);
    }

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $enabled = true;

    #[ORM\Column(type: Types::STRING, length: 5, options: ['default' => 'es'])]
    public string $defaultLang = 'es';

    #[ORM\OneToMany(mappedBy: 'form', targetEntity: FormSection::class, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public private(set) Collection $sections;

    // Estado runtime para el property hook de $name (no se persiste)
    private ?string $activeLocale = null;

    public function __construct()
    {
        $this->uuid = new Uuid\UuidV7();        // new en initializer + symfony/uid v7
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
        // propaga a relaciones también si están cargadas
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
```

Notar:
- **`public private(set)`** en `$id`, `$tag`, `$uuid` (via trait), `$sections` → lectura directa, escritura controlada.
- **Property hook virtual** `$name` con cadena de fallback de locale, **sin** `getName()` ni `setName()`.
- **`new` en constructor** para `UuidV7()`.
- **Trait con `public private(set)`** sobre `$uuid` (definido en `HasUuidTrait`).
- **`#[\Override]`** se aplica en métodos que sobrescriben (no aplica aquí porque no hay overrides).

---

### 6.1 Esquema de BD

#### `form`
| Columna | Tipo | Notas |
|---|---|---|
| `id` | BIGINT PK AUTO | |
| `uuid` | UUID UNIQUE | Symfony UID |
| `name` | VARCHAR(255) | |
| `tag` | VARCHAR(100) UNIQUE | Identificador funcional |
| `enabled` | BOOLEAN DEFAULT TRUE | |
| `default_lang` | VARCHAR(5) DEFAULT 'es' | |
| `parameters` | JSON | ValueObject `FormParameters` |
| `translations` | JSON NULL | `{field: {locale: value}}` |
| `deleted_at` | TIMESTAMP NULL | Soft delete (Gedmo SoftDeleteable) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `form_section`
| Columna | Tipo | Notas |
|---|---|---|
| `id` | BIGINT PK AUTO | |
| `uuid` | UUID UNIQUE | |
| `form_id` | BIGINT FK → form.id | |
| `name` | VARCHAR(255) | |
| `tag` | VARCHAR(100) | UNIQUE(form_id, tag) |
| `description` | TEXT NULL | |
| `position` | INT DEFAULT 0 | |
| `enabled` | BOOLEAN DEFAULT TRUE | |
| `parameters` | JSON | |
| `translations` | JSON NULL | |
| `deleted_at` | TIMESTAMP NULL | Soft delete |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `form_group`
| Columna | Tipo | Notas |
|---|---|---|
| `id` | BIGINT PK AUTO | |
| `uuid` | UUID UNIQUE | |
| `section_id` | BIGINT FK → form_section.id | |
| `name` | VARCHAR(255) | |
| `tag` | VARCHAR(100) | UNIQUE(section_id, tag) |
| `description` | TEXT NULL | |
| `position` | INT DEFAULT 0 | |
| `enabled` | BOOLEAN DEFAULT TRUE | |
| `parameters` | JSON | incluye `type_render` (registry de GroupRender) |
| `translations` | JSON NULL | |
| `deleted_at` | TIMESTAMP NULL | Soft delete |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `form_field`
| Columna | Tipo | Notas |
|---|---|---|
| `id` | BIGINT PK AUTO | |
| `uuid` | UUID UNIQUE | |
| `group_id` | BIGINT FK → form_group.id | |
| `name` | VARCHAR(255) | |
| `tag` | VARCHAR(100) | UNIQUE(group_id, tag) |
| `type` | VARCHAR(50) | Nombre del FieldType registrado |
| `description` | TEXT NULL | |
| `attributes` | JSON | ValueObject `FieldAttributes` |
| `parameters` | JSON | ValueObject `FieldParameters` |
| `position` | INT DEFAULT 0 | |
| `enabled` | BOOLEAN DEFAULT TRUE | |
| `translations` | JSON NULL | |
| `deleted_at` | TIMESTAMP NULL | Soft delete |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `form_option_general`
| Columna | Tipo | Notas |
|---|---|---|
| `id` | BIGINT PK AUTO | |
| `uuid` | UUID UNIQUE | |
| `name` | VARCHAR(255) | |
| `tag` | VARCHAR(100) UNIQUE | Identificador del agrupador |
| `parameters` | JSON | |
| `translations` | JSON NULL | |
| `deleted_at` | TIMESTAMP NULL | Soft delete |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `form_option_general_value`
| Columna | Tipo | Notas |
|---|---|---|
| `id` | BIGINT PK AUTO | |
| `uuid` | UUID UNIQUE | |
| `group_id` | BIGINT FK → form_option_general.id | |
| `text` | VARCHAR(255) | |
| `tag` | VARCHAR(100) | UNIQUE(group_id, tag) |
| `description` | TEXT NULL | |
| `parameters` | JSON | Puede incluir `icon`, `color`, etc |
| `position` | INT DEFAULT 0 | |
| `enabled` | BOOLEAN DEFAULT TRUE | |
| `translations` | JSON NULL | |
| `deleted_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

> **Nota PG/MySQL**: todas las columnas JSON usan `Doctrine\DBAL\Types\Types::JSON` que se mapea automáticamente a `jsonb` en PostgreSQL y `json` en MySQL/MariaDB. Si el proyecto necesita índices GIN específicos de PG, se documenta cómo agregarlos en una migración del proyecto.

> **Nota Soft Delete**: las 6 entidades usan `Gedmo\SoftDeleteable` (vía `stof/doctrine-extensions-bundle`). El paquete activa automáticamente el filtro `softdeleteable` en su servicio de conexión, así los repos del paquete devuelven solo registros vivos. Para queries que necesiten incluir eliminados, se documenta cómo desactivar el filtro temporalmente: `$em->getFilters()->disable('softdeleteable')`.

### 6.2 Traits compartidos

#### `HasTranslationsTrait`
Aplicado a: `Form`, `FormSection`, `FormGroup`, `FormField`, `FormOptionGeneral`, `FormOptionGeneralValue`.

```php
<?php
declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait HasTranslationsTrait
{
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public array|null $translations = null {
        set(array|null $value) => $this->translations = $value;
    }

    public function getTranslation(string $locale, string $field): string|null
    {
        return $this->translations[$locale][$field] ?? null;
    }

    public function setTranslation(string $locale, string $field, string|null $value): void
    {
        if (null === $value) {
            unset($this->translations[$locale][$field]);
            return;
        }

        $this->translations[$locale][$field] = $value;
    }
}
```

> **Estructura canónica**: `{locale: {field: value}}`. Ejemplo: `{"es": {"name": "Correo", "placeholder": "Ingresa correo"}, "en": {"name": "Email", "placeholder": "Enter email"}}`.
> **El campo `translations` se incluye en los DTOs de salida** — el frontend recibe todas las traducciones disponibles y maneja la presentación por locale sin re-request. El backend sigue siendo dueño de la verdad: resuelve el locale activo en los campos principales del DTO (`name`, `description`, `placeholder`) y expone el JSON completo en `translations`.
> **Patrón de uso con property hooks**: cada entidad declara su backing field privado (`$rawName`) mapeado a la columna y un property hook público `$name` que llama internamente a `getTranslation()` con fallback. El trait provee la mecánica; las entidades exponen las propiedades.

#### `HasUuidTrait`
UUID v7 (Symfony UID) por orden temporal.

#### `HasParametersTrait`
JSON merge recursivo.

#### `HasTimestampsTrait`
`created_at` + `updated_at` con `#[ORM\PrePersist]` / `#[ORM\PreUpdate]`.

#### `HasSoftDeleteTrait`
Wrapper sobre `Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity` para mantener un naming consistente. Aplicado a las 6 entidades.

---

## 7. Tipos de campo (Strategy + Registry)

### 7.1 Contrato

```php
<?php
declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Contract;

use Letkode\FormSchema\Domain\ValueObject\FieldAttributes;

interface FieldTypeInterface
{
    /**
     * Nombre único del tipo. Ej: 'string', 'email', 'rating'.
     * Será el valor guardado en form_field.type.
     */
    public static function getName(): string;

    /**
     * Indica si este tipo se nutre de opciones (list, radio, etc).
     */
    public function takesOptions(): bool;

    /**
     * Atributos por defecto del tipo. Se hace merge recursivo
     * con los attributes guardados en BD.
     */
    public function getDefaultAttributes(): FieldAttributes;

    /**
     * Formatea el default_value según el tipo.
     * Ej: 'date' resuelve "+1 day" a fecha real.
     */
    public function formatDefaultValue(mixed $rawValue): mixed;
}
```

> **Nota**: el método `validate()` se reserva para v2. Los `FieldType` de v1 son meramente declarativos (forma, defaults, opciones). La validación de respuestas del usuario queda en el proyecto consumidor.

### 7.2 Base abstracta

```php
abstract class AbstractFieldType implements FieldTypeInterface
{
    public function takesOptions(): bool
    {
        return false;
    }

    public function getDefaultAttributes(): FieldAttributes
    {
        return FieldAttributes::default();
    }

    public function formatDefaultValue(mixed $rawValue): mixed
    {
        return $rawValue;
    }
}
```

### 7.3 Ejemplos de implementación

- **`StringFieldType`** — defaults básicos, no toma opciones.
- **`DateFieldType`** — implementa `formatDefaultValue` para resolver modifiers tipo `"+1 day"` usando `\DateTimeImmutable::modify()`.
- **`DateRangeFieldType`** — `formatDefaultValue` devuelve `['from','to']` aplicando `DateRangeHelper` de `letkode/helpers`.
- **`ListFieldType`** — `takesOptions(): true`. Default `attributes.create/edit/show = true`.
- **`SwitchFieldType` / `CheckboxFieldType`** — `takesOptions(): true`. Usan `ValueToBooleanHelper` para normalizar el `default_value`.
- **`RatingFieldType`** — `takesOptions(): true`. Las opciones aportan `icon` y `value` desde sus `parameters` (`{icon: 'star', color: '#f59e0b'}`).
- **`EmailFieldType`** — solo declarativo en v1. En v2 podrá usar `IsValidEmailHelper`.
- **`PasswordFieldType`** — `attributes.extra.min_strength` opcional.

### 7.4 Registry estilo Doctrine

```php
final class FieldTypeRegistry
{
    /** @var array<string, FieldTypeInterface> */
    private array $types = [];

    public function register(FieldTypeInterface $type): void
    {
        $name = $type::getName();
        if (isset($this->types[$name])) {
            throw new FieldTypeAlreadyRegisteredException($name);
        }
        $this->types[$name] = $type;
    }

    public function override(FieldTypeInterface $type): void
    {
        $this->types[$type::getName()] = $type;
    }

    public function get(string $name): FieldTypeInterface
    {
        return $this->types[$name]
            ?? throw new UnknownFieldTypeException($name);
    }

    public function has(string $name): bool
    {
        return isset($this->types[$name]);
    }

    /** @return array<string, FieldTypeInterface> */
    public function all(): array
    {
        return $this->types;
    }
}
```

### 7.5 Auto-registro desde el proyecto

**Vía PHP attribute + autowire:**

```php
#[AsFieldType]
final class CurrencyFieldType extends AbstractFieldType
{
    public static function getName(): string { return 'currency'; }
}
```

El proyecto solo necesita declarar la clase; autowiring + `registerForAutoconfiguration` (que aplica el tag `form_schema.field_type`) + `#[AutowireIterator('form_schema.field_type')]` en el constructor del `FieldTypeRegistry` hacen el resto. **Sin compiler passes** — ver sección 2.8.3 para el patrón.

---

## 8. Atributos y parámetros (Value Objects)

### 8.1 `FieldAttributes` — schema base

```php
<?php
declare(strict_types=1);

namespace Letkode\FormSchema\Domain\ValueObject;

final readonly class FieldAttributes
{
    public function __construct(
        public bool $required = false,
        public bool $readonly = false,
        public UniqueRule $unique = new UniqueRule(),
        public FilterRule $filter = new FilterRule(),
        public bool $show = true,
        public bool $edit = true,
        public bool $create = true,
        private array $dynamic = [],        // claves custom del proyecto, transparente desde fuera
    ) {}

    public static function default(): self
    {
        return new self();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            required: $data['required'] ?? false,
            readonly: $data['readonly'] ?? false,
            unique:   UniqueRule::fromArray($data['unique'] ?? []),
            filter:   FilterRule::fromArray($data['filter'] ?? []),
            show:     $data['show']   ?? true,
            edit:     $data['edit']   ?? true,
            create:   $data['create'] ?? true,
            dynamic:  array_diff_key(
                $data,
                array_flip(['required','readonly','unique','filter','show','edit','create'])
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'required' => $this->required,
            'readonly' => $this->readonly,
            'unique'   => $this->unique->toArray(),
            'filter'   => $this->filter->toArray(),
            'show'     => $this->show,
            'edit'     => $this->edit,
            'create'   => $this->create,
            ...$this->dynamic,              // todo al mismo nivel, plano
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->dynamic[$key] ?? $default;
    }
}

final readonly class UniqueRule
{
    public function __construct(
        public bool $enabled = false,
        public ?string $entity = null,
        public ?string $method = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: $data['enabled'] ?? false,
            entity:  $data['entity']  ?? null,
            method:  $data['method']  ?? null,
        );
    }

    public function toArray(): array
    {
        return ['enabled' => $this->enabled, 'entity' => $this->entity, 'method' => $this->method];
    }
}

final readonly class FilterRule
{
    public function __construct(
        public bool $enabled = false,
        public ?string $key = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: $data['enabled'] ?? false,
            key:     $data['key']     ?? null,
        );
    }

    public function toArray(): array
    {
        return ['enabled' => $this->enabled, 'key' => $this->key];
    }
}
```

### 8.2 Extensión desde el proyecto

El proyecto agrega atributos custom directamente en el JSON de BD al mismo nivel que los built-in. No requiere código adicional:

```php
$field->setAttributes([
    'required' => true,
    'create'   => true,
    'preview'  => false,    // atributo custom del proyecto
    'export'   => true,     // atributo custom del proyecto
]);
```

`FieldAttributes::fromArray()` captura las claves desconocidas en `$dynamic` internamente. Desde fuera se accede con `$attributes->get('preview')` y `toArray()` los serializa al mismo nivel que los built-in.

Para usar un atributo custom como contexto de filtrado:

```php
$this->resolver
    ->schema('contact_form')
    ->withContext('preview')    // filtra fields con attributes.preview === false
    ->resolve();
```

Si el contexto solicitado no existe como clave en ningún atributo del field, el resolver lanza `UnknownContextException`.

### 8.3 `FieldParameters`, `FormParameters`, `SectionParameters`, `GroupParameters`

Cada nivel del schema tiene su propio VO de parámetros. Estructura mínima:

- `FormParameters`: `default_locale`, `available_locales[]`, `extra[]`.
- `SectionParameters`: `extra[]`.
- `GroupParameters`: `type_render` ('simple' | 'matrix' | custom), `extra[]`.
- `FieldParameters`: `placeholder`, `default_value`, `style[]`, `options` (resuelto en runtime), `set_options` (objeto con `type` + `params`, config de fuente única), `extra[]`.

Todos siguen el mismo patrón readonly + `fromArray` / `toArray` / `get($key, $default)`.

---

## 9. Opciones (Options Sources)

### 9.1 Contrato

```php
<?php
declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Contract;

interface OptionsSourceInterface
{
    /** Nombre único: 'general', 'entity', etc. */
    public static function getName(): string;

    /**
     * @param array<string,mixed> $parameters Configuración guardada en el field
     * @return list<OptionDTO>
     */
    public function resolve(array $parameters, ?string $locale = null): array;
}
```

### 9.2 Configuración en BD (`FieldParameters.set_options`)

Cada field "con opciones" guarda en `parameters.set_options` un objeto con la fuente única. Un field tiene exactamente una fuente de opciones — o `general` o `entity` (o cualquier fuente custom registrada).

```json
// Fuente interna (catálogo general)
{
  "set_options": {
    "type": "general",
    "params": {
      "set": "civil_status",
      "with_all_option": false
    }
  }
}
```

```json
// Fuente externa (entidad del proyecto)
{
  "set_options": {
    "type": "entity",
    "params": {
      "entity": "App\\CRM\\Repository\\CountryRepository",
      "method": "findForFormOptions"
    }
  }
}
```

### 9.3 Fuentes built-in

#### `OptionsGeneralSource`
- `type: 'general'`
- Busca un `FormOptionGeneral` por `params.set` (tag).
- Devuelve sus `FormOptionGeneralValue` enabled, traducidos.
- Params soportados: `set` (required), `with_all_option`, `all_option_params`, `id_column`, `text_column`.

#### `OptionsEntitySource`
- `type: 'entity'`
- Llama `$entityManager->getRepository($params['entity'])->{$params['method']}($context)`.
- **Default method**: `findForFormOptions(array $context)`.
- Valida que el par `clase + método` esté registrado vía `#[AsFormOptionsProvider]` antes de llamar — si no está registrado lanza `UnauthorizedOptionsMethodException`.
- Params: `entity` (FQCN, required), `method` (default `findForFormOptions`), `id_column`, `text_column`, `with_all_option`.

### 9.4 Registro de repositorios como proveedores de opciones

El proyecto marca sus repositorios con `#[AsFormOptionsProvider]` para habilitarlos como fuente de opciones. El atributo es repetible — cada declaración habilita un método específico:

```php
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class AsFormOptionsProvider
{
    public function __construct(
        public string|array $method = 'findForFormOptions',
    ) {}
}
```

Uso en el proyecto:

```php
// Un método (más común)
#[AsFormOptionsProvider]
class CountryRepository extends ServiceEntityRepository
{
    public function findForFormOptions(array $context): array { /* ... */ }
}

// Múltiples métodos — Opción A: atributo repetible
#[AsFormOptionsProvider(method: 'findForFormOptions')]
#[AsFormOptionsProvider(method: 'findWithAgreementForForm')]
class ProvinceRepository extends ServiceEntityRepository { /* ... */ }

// Múltiples métodos — Opción B: array en una sola declaración
#[AsFormOptionsProvider(method: ['findForFormOptions', 'findWithAgreementForForm'])]
class RegionRepository extends ServiceEntityRepository { /* ... */ }
```

`OptionsEntitySource` construye en bootstrap un mapa `clase → [métodos permitidos]` y valida contra él en cada llamada.

### 9.5 Registry de fuentes

`OptionsSourceRegistry` idéntico en concepto al `FieldTypeRegistry`. Tag: `form_schema.options_source`. Attribute: `#[AsOptionsSource]`. Sin compiler pass — recibe sus servicios vía `#[AutowireIterator('form_schema.options_source')]`.

La clase es `final`. Expone el contrato `OptionsSourceRegistryInterface` (`get`, `has`, `all`) para que los consumidores internos (como `OptionsResolver`) dependan de la abstracción y puedan mockearse en tests unitarios.

El proyecto puede registrar fuentes adicionales (ej. `OptionsApiSource` que va a un endpoint externo).

---

## 10. Sistema de interacciones de fields

Las interacciones permiten que el schema declare **comportamiento dinámico** que el UI debe ejecutar al producirse eventos sobre un field. El bundle almacena la configuración de forma puramente declarativa; no ejecuta ni valida la lógica.

### 10.1 Columna en BD

`form_field.interactions` — JSON nullable. Array de objetos `FieldInteraction`.

### 10.2 Estructura de `FieldInteraction`

```php
final readonly class FieldInteraction
{
    public string $trigger;            // 'change' | 'blur' | 'focus' | 'input'
    public string $action;             // tipo de acción (ver tabla)
    public string|array|null $target;  // tag(s) del field afectado; null = self
    public array $condition;           // condición opcional sobre el valor actual
    public array $params;              // parámetros específicos de la acción
}
```

### 10.3 Contrato `InteractionHandlerInterface`

El sistema usa el mismo patrón **Strategy + Registry** que `FieldType` y `OptionsSource`. Cada tipo de acción es una clase que implementa la interfaz:

```php
interface InteractionHandlerInterface
{
    public static function getName(): string;   // nombre de la acción, ej. 'filter_options'
    public function getDefaultParams(): array;  // params mergeados con los almacenados en BD
}
```

El resolver hace `array_replace($handler->getDefaultParams(), $storedParams)` — los params de BD sobreescriben los defaults. El UI siempre recibe un DTO con params completos.

### 10.4 Handlers built-in y sus defaults

| Handler | `getName()` | `getDefaultParams()` |
|---|---|---|
| `FilterOptionsInteractionHandler` | `filter_options` | `['mode' => 'server']` |
| `ToggleVisibilityInteractionHandler` | `toggle_visibility` | `[]` |
| `ToggleRequiredInteractionHandler` | `toggle_required` | `[]` |
| `SetValueInteractionHandler` | `set_value` | `[]` |
| `AjaxValidateInteractionHandler` | `ajax_validate` | `['method' => 'GET', 'debounce' => 0]` |
| `SetDateConstraintInteractionHandler` | `set_date_constraint` | `['constraint' => 'min']` |
| `ComputeInteractionHandler` | `compute` | `['decimals' => null]` |

### 10.5 Extensibilidad — handlers del proyecto

El bundle registra `InteractionHandlerInterface` para autoconfiguration con tag `form_schema.interaction_handler`. Cualquier clase del proyecto con `#[AsInteractionHandler]` queda disponible automáticamente:

```php
#[AsInteractionHandler]
final class SendWebhookInteractionHandler extends AbstractInteractionHandler
{
    public static function getName(): string { return 'send_webhook'; }

    public function getDefaultParams(): array
    {
        return ['method' => 'POST', 'retry' => 0];
    }
}
```

Si el resolver encuentra un `action` no registrado en el registry lanza `UnknownInteractionHandlerException`.

### 10.6 Condition operators

`equals`, `not_equals`, `in`, `not_in`, `truthy`, `falsy`. Condition `{}` = ejecutar siempre.

### 10.7 En el DTO

`FieldDTO.interactions` expone `list<InteractionDTO>` (vacío si no hay interacciones). Params = merge de `getDefaultParams()` + params almacenados en BD.

---

## 11. Renders estructurales en 3 niveles (Strategy + Registry)


El paquete entiende que **cada nivel jerárquico tiene su propio modo de render** y cada uno es extensible. Los tres niveles (`Form`, `FormSection`, `FormGroup`) tienen un campo `parameters.type_render` que apunta a un render registrado.

| Nivel | Built-in | Caso de uso |
|---|---|---|
| `Form` | `simple`, `stepper`, `wizard`, `tabs`, `modal` | Cómo se navega entre secciones |
| `FormSection` | `simple`, `accordion`, `tabs`, `collapsible` | Cómo se presentan los grupos dentro de una sección |
| `FormGroup` | `simple`, `matrix`, `tabs` | Cómo se distribuyen los fields dentro de un grupo |

Cada nivel tiene su propia interfaz, su propio registry y su propio atributo de auto-discovery — pero todos extienden un contrato base común.

### 10.1 Contrato base

```php
<?php
declare(strict_types=1);

namespace Letkode\FormSchema\Domain\Contract;

interface StructureRenderInterface
{
    /** Nombre único dentro de su nivel. */
    public static function getName(): string;

    /**
     * Metadatos adicionales que se inyectan en el DTO del nivel correspondiente.
     * Lo que devuelva esto se ubica en `$dto->render['metadata']` y el frontend lo consume.
     *
     * @param array<string,mixed> $parameters  parameters[] del nivel (Form/Section/Group)
     * @return array<string,mixed>
     */
    public function renderMeta(array $parameters): array;
}

interface FormRenderInterface extends StructureRenderInterface {}
interface SectionRenderInterface extends StructureRenderInterface {}
interface GroupRenderInterface extends StructureRenderInterface {}
```

> **Por qué 3 interfaces y no 1**: para que el autoconfiguration de Symfony pueda tag-ear correctamente por nivel (`form_schema.form_render` ≠ `section_render` ≠ `group_render`) y un `FormRender` no pueda usarse accidentalmente como `GroupRender`. La interfaz base solo existe para DRY del contrato.

### 10.2 Form renders built-in

```php
#[AsFormRender]
final class StepperFormRender implements FormRenderInterface
{
    #[\Override]
    public static function getName(): string { return 'stepper'; }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'navigation' => $parameters['stepper']['navigation'] ?? 'horizontal', // horizontal|vertical
            'show_progress' => $parameters['stepper']['show_progress'] ?? true,
            'allow_skip' => $parameters['stepper']['allow_skip'] ?? false,
            'persist_on_navigate' => $parameters['stepper']['persist_on_navigate'] ?? true,
        ];
    }
}

#[AsFormRender]
final class WizardFormRender implements FormRenderInterface
{
    public static function getName(): string { return 'wizard'; }

    public function renderMeta(array $parameters): array
    {
        // como stepper, pero fuerza completar cada paso antes de avanzar
        return [
            'navigation' => 'horizontal',
            'show_progress' => true,
            'enforce_completion' => true,
            'allow_skip' => false,
        ];
    }
}

#[AsFormRender]
final class SimpleFormRender implements FormRenderInterface
{
    public static function getName(): string { return 'simple'; }
    public function renderMeta(array $parameters): array { return []; }
}

#[AsFormRender]
final class TabsFormRender implements FormRenderInterface
{
    public static function getName(): string { return 'tabs'; }
    public function renderMeta(array $parameters): array
    {
        return [
            'position' => $parameters['tabs']['position'] ?? 'top', // top|left
            'lazy_load' => $parameters['tabs']['lazy_load'] ?? false,
        ];
    }
}
```

> **`stepper` semántico**: en el modo stepper, **cada `FormSection` es un paso**. El frontend usa `form.sections[]` como la secuencia de pasos y `form.render.metadata.navigation/show_progress/...` como configuración.

### 10.3 Section renders built-in

```php
#[AsSectionRender]
final class AccordionSectionRender implements SectionRenderInterface
{
    public static function getName(): string { return 'accordion'; }
    public function renderMeta(array $parameters): array
    {
        return [
            'allow_multiple_open' => $parameters['accordion']['allow_multiple_open'] ?? false,
            'first_open' => $parameters['accordion']['first_open'] ?? true,
        ];
    }
}

#[AsSectionRender]
final class CollapsibleSectionRender implements SectionRenderInterface
{
    public static function getName(): string { return 'collapsible'; }
    public function renderMeta(array $parameters): array
    {
        return ['default_collapsed' => $parameters['collapsible']['default_collapsed'] ?? false];
    }
}

// SimpleSectionRender y TabsSectionRender siguen el mismo patrón
```

### 10.4 Group renders built-in

```php
#[AsGroupRender]
final class MatrixGroupRender implements GroupRenderInterface
{
    public static function getName(): string { return 'matrix'; }
    public function renderMeta(array $parameters): array
    {
        return [
            'rows' => $parameters['matrix']['rows'] ?? [],
            'cols' => $parameters['matrix']['cols'] ?? [],
        ];
    }
}

// SimpleGroupRender y TabsGroupRender siguen el mismo patrón
```

### 10.5 Registries

Tres registries idénticos en estructura, cada uno indexa su nivel:

```php
final class FormRenderRegistry
{
    /** @var array<string, FormRenderInterface> */
    private array $renders = [];

    public function register(FormRenderInterface $render): void { /* ... */ }
    public function get(string $name): FormRenderInterface { /* ... */ }
    public function has(string $name): bool { /* ... */ }
}
// SectionRenderRegistry y GroupRenderRegistry son análogos
```

### 10.6 Custom desde el proyecto

Cualquier nivel se extiende con su propio atributo:

```php
#[AsFormRender]
final class KanbanFormRender implements FormRenderInterface
{
    public static function getName(): string { return 'kanban'; }
    public function renderMeta(array $parameters): array { /* ... */ }
}

#[AsSectionRender]
final class CardSectionRender implements SectionRenderInterface { /* ... */ }

#[AsGroupRender]
final class WizardGroupRender implements GroupRenderInterface { /* ... */ }
```

Auto-discovery vía `registerForAutoconfiguration` por nivel:

```php
$builder->registerForAutoconfiguration(FormRenderInterface::class)
    ->addTag('form_schema.form_render');
$builder->registerForAutoconfiguration(SectionRenderInterface::class)
    ->addTag('form_schema.section_render');
$builder->registerForAutoconfiguration(GroupRenderInterface::class)
    ->addTag('form_schema.group_render');
```

Tres registries (`FormRenderRegistry`, `SectionRenderRegistry`, `GroupRenderRegistry`) reciben sus servicios vía `#[AutowireIterator('form_schema.X_render')]` en el constructor. Sin compiler passes.

### 10.7 Aplicación en el resolver

El resolver consulta el registro correspondiente al construir cada nivel del DTO:

```
FormSchemaResolver::resolve()
   ├─ formRender   = FormRenderRegistry::get($form->parameters['type_render']    ?? 'simple');
   ├─ formMeta     = formRender->renderMeta($form->parameters);
   └─ for each section:
        ├─ sectionRender = SectionRenderRegistry::get($section->parameters['type_render'] ?? 'simple');
        ├─ sectionMeta   = sectionRender->renderMeta($section->parameters);
        └─ for each group:
             ├─ groupRender = GroupRenderRegistry::get($group->parameters['type_render'] ?? 'simple');
             └─ groupMeta   = groupRender->renderMeta($group->parameters);
```

Cada meta se inyecta en el DTO de su nivel dentro de `render` como `{"type": "...", "metadata": {...}}`.

---

## 12. Integración con `letkode/helpers`

El paquete declara `letkode/helpers: ^1` como dependencia y reutiliza:

| FieldType / componente | Helper reutilizado | Uso |
|---|---|---|
| `EmailFieldType::validate()` | `IsValidEmailHelper` | Validación de formato (en preparación para v2) |
| `DateRangeFieldType::formatDefaultValue()` | `DateRangeHelper` | Construye el rango desde `from`/`to` |
| `SwitchFieldType` / `CheckboxFieldType` | `ValueToBooleanHelper` | Normaliza `'SI'/'NO'/'true'/'1'` |
| Tag auto-generation (helper opcional) | `SlugifyHelper` | Para auto-generar `tag` desde `name` si no se especifica |
| Excepciones del paquete | `HelperException` | Patrón base con `translationKey` para i18n de errores |

Esto evita duplicar lógica y mantiene un comportamiento consistente con el resto de tus proyectos. Si en algún momento `letkode/helpers` evoluciona, el paquete se beneficia automáticamente.

---

## 13. DTOs

`final readonly` classes con `JsonSerializable`. Cada nivel jerárquico expone la llave `render` con `type` y `metadata` proveniente del render aplicado.

```php
final readonly class FormDTO implements \JsonSerializable
{
    /** @param list<SectionDTO> $sections */
    public function __construct(
        public string $id,                  // uuid
        public string $name,
        public string $tag,
        public string $locale,
        public string $defaultLocale,
        public bool $enabled,
        public array $parameters,
        public string $renderType,          // ej. 'stepper', 'simple', 'wizard'
        public array $renderMeta,           // output de FormRenderInterface::renderMeta()
        public array|null $translations,    // {locale: {field: value}} completo
        public array $sections,
    ) {}

    // jsonSerialize() emite: render => ['type' => $renderType, 'metadata' => $renderMeta]
    public function jsonSerialize(): array { /* ... */ }
}

final readonly class SectionDTO implements \JsonSerializable
{
    /** @param list<GroupDTO> $groups */
    public function __construct(
        public string $id,
        public string $name,
        public string $tag,
        public ?string $description,
        public int $position,
        public bool $enabled,
        public array $parameters,
        public string $renderType,          // ej. 'accordion', 'tabs'
        public array $renderMeta,           // output de SectionRenderInterface::renderMeta()
        public array|null $translations,    // {locale: {field: value}} completo
        public array $groups,
    ) {}
}

final readonly class GroupDTO implements \JsonSerializable
{
    /** @param list<FieldDTO> $fields */
    public function __construct(
        public string $id,
        public string $name,
        public string $tag,
        public ?string $description,
        public int $position,
        public bool $enabled,
        public array $parameters,
        public string $renderType,          // ej. 'matrix', 'simple'
        public array $renderMeta,           // output de GroupRenderInterface::renderMeta()
        public array|null $translations,    // {locale: {field: value}} completo
        public array $fields,
    ) {}
}

final readonly class FieldDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,               // resuelto al locale activo
        public string $tag,
        public string $type,
        public ?string $description,       // resuelto al locale activo
        public array $attributes,          // FieldAttributes::toArray()
        public array $parameters,          // FieldParameters::toArray() (sin set_options)
        public int $position,
        public bool $enabled,
        public ?string $placeholder,       // resuelto al locale activo
        public mixed $defaultValue,        // serializado como default_value
        public array $style,
        public array $options,             // list<OptionDTO>
        public array|null $translations,   // {locale: {field: value}} completo
        public array $interactions = [],   // list<InteractionDTO> — vacío si no hay
    ) {}
}

final readonly class InteractionDTO implements \JsonSerializable
{
    public function __construct(
        public string $trigger,            // 'change' | 'blur' | 'focus' | 'input'
        public string $action,             // 'filter_options' | 'toggle_visibility' | 'toggle_required' | 'set_value' | 'ajax_validate' | 'set_date_constraint' | 'compute'
        public string|array|null $target,  // tag(s) del field afectado; null = self
        public array $condition,           // ['operator' => ..., 'value' => ...] o []
        public array $params,              // config específica de cada action
    ) {}
}

final readonly class OptionDTO implements \JsonSerializable
{
    public function __construct(
        public string|int $value,
        public string $text,
        public ?string $tag = null,
        public ?string $icon = null,
        public ?string $color = null,
        public int $position = 0,
        public array $extra = [],
    ) {}
}

final readonly class OptionGroupDTO implements \JsonSerializable
{
    /** Para list-group: agrupa OptionDTOs bajo un texto. */
    public function __construct(
        public string $text,
        /** @var list<OptionDTO> */
        public array $options,
    ) {}
}
```

---

## 14. Servicio público: `FormSchemaResolver`

### 13.1 Interfaz

```php
interface FormSchemaResolverInterface
{
    public function schema(string $tag): self;
    public function withLocale(string $locale): self;
    public function withContext(string $action): self;  // 'create' | 'edit' | 'show' | custom
    public function includingSections(array $tags): self;
    public function excludingSections(array $tags): self;
    public function includingGroups(array $tags): self;
    public function excludingGroups(array $tags): self;
    public function resolve(): FormDTO;
    public function toArray(): array;
}
```

### 13.2 Flujo interno

```
schema(tag)         → guarda el tag, nada más
withLocale(locale)  → guarda el locale, clona la instancia
withContext(action) → guarda el contexto, clona la instancia
includingSections / excludingSections / includingGroups / excludingGroups → guardan filtros, clonan
resolve():
   1. FormRepository->findOneByTag($tag) or throw FormNotFoundException
   2. Construir StructureFilter desde flags.
   3. Resolver locale efectivo: $locale ?? $form->defaultLang ?? bundle.default_locale.
      Cadena de fallback: locale solicitado → $form->defaultLang → valor crudo del campo.
   4. Resolver Form-level render:
      $formRender = FormRenderRegistry::get($form->parameters['type_render'] ?? 'simple');
      $formMeta   = $formRender->renderMeta($form->parameters);
   5. Recorrer Form → Sections → Groups → Fields aplicando filtros (StructureFilter).
   6. Para cada Section:
      $sectionRender = SectionRenderRegistry::get($section->parameters['type_render'] ?? 'simple');
      $sectionMeta   = $sectionRender->renderMeta($section->parameters);
   7. Para cada Group:
      $groupRender = GroupRenderRegistry::get($group->parameters['type_render'] ?? 'simple');
      $groupMeta   = $groupRender->renderMeta($group->parameters);
   8. Para cada Field:
      a. $type = FieldTypeRegistry::get($field->type)
      b. $defaultAttrs = $type->getDefaultAttributes()
      c. $attrs = FieldAttributes::fromArray(
            array_replace_recursive($defaultAttrs->toArray(), $field->attributes)
         )
      d. Si $context está seteado:
         - Buscar la clave $context en $attrs->toArray().
         - Si no existe → lanzar UnknownContextException($context).
         - Si existe y su valor === false → excluir el field.
         - Sin $context → incluir todos los fields sin filtrar.
      e. Si $type->takesOptions(): OptionsResolver->resolve($field->parameters['set_options'] ?? [], $locale)
      f. $defaultValue = $type->formatDefaultValue($field->parameters['default_value'])
      g. Construir FieldDTO con i18n aplicado (name/description/placeholder resueltos al locale activo vía property hook + HasTranslationsTrait) e incluir `translations` completo.
   9. Componer GroupDTO (con $groupMeta + $translations) → SectionDTO (con $sectionMeta + $translations) → FormDTO (con $formMeta + $translations).
toArray(): FormDTO->jsonSerialize()
```

> **Locale dinámico**: el `withLocale()` permite que el controller pase el locale que el usuario eligió (típicamente desde `$request->getLocale()` o desde el perfil del usuario). El paquete no asume ningún flujo de locale switching; solo lo recibe y lo aplica.

> **Renders en cascada**: cada nivel resuelve su render independientemente. Esto significa que puedes tener un `Form` con `type_render: stepper` donde cada `Section` (cada paso) tiene `type_render: accordion` y cada `Group` (dentro del accordion) tiene `type_render: matrix`. El frontend recibe los tres `renderMeta` y compone la UI completa.

### 13.3 Inmutabilidad del builder

`FormSchemaResolver` es un servicio singleton; cada llamada retorna una nueva instancia clonada con la mutación aplicada, para que sea seguro en concurrencia (ej. controllers async).

---

## 15. Cache (PSR-6 opt-in)

### 14.1 Configuración

```yaml
letkode_form_schema:
    cache:
        enabled: false                    # default
        pool: cache.app                   # cualquier PSR-6 pool
        ttl: 3600                         # segundos; 0 = forever
        key_prefix: 'fsb'
```

### 14.2 Implementación: decorator

```php
final class CachedFormSchemaResolver implements FormSchemaResolverInterface
{
    public function __construct(
        private FormSchemaResolverInterface $inner,
        private CacheItemPoolInterface $cache,
        private int $ttl,
        private string $prefix,
    ) {}

    public function resolve(): FormDTO
    {
        $key = $this->buildKey();
        $item = $this->cache->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }
        $dto = $this->inner->resolve();
        $item->set($dto)->expiresAfter($this->ttl > 0 ? $this->ttl : null);
        $this->cache->save($item);
        return $dto;
    }

    private function buildKey(): string
    {
        $inclSections = $this->includingSections;
        $exclSections = $this->excludingSections;
        $inclGroups   = $this->includingGroups;
        $exclGroups   = $this->excludingGroups;

        sort($inclSections);
        sort($exclSections);
        sort($inclGroups);
        sort($exclGroups);

        return $this->prefix . '.' . hash('xxh3', serialize([
            'tag'           => $this->tag,
            'locale'        => $this->locale,
            'context'       => $this->context,
            'incl_sections' => $inclSections,
            'excl_sections' => $exclSections,
            'incl_groups'   => $inclGroups,
            'excl_groups'   => $exclGroups,
        ]));
    }

    // ...delega el resto al inner
}
```

### 14.3 Invalidación

Servicio público `FormSchemaCacheInvalidator`:

```php
$invalidator->invalidate(string $tag): void;
$invalidator->invalidateAll(): void;
```

Implementado vía cache tags (Symfony Cache `TagAwareAdapterInterface`). Si el pool no soporta tags, fallback a borrar por patrón de prefijo.

Se documenta que el proyecto debe invocar `invalidate($tag)` en sus listeners `postUpdate` / `postPersist` / `postRemove` de las entidades `Form`/`FormSection`/`FormGroup`/`FormField` cuando esté usando cache. Opcionalmente el paquete provee un `DoctrineCacheInvalidationSubscriber` opt-in que lo hace automáticamente.

---

## 16. Migraciones

### 15.1 Estrategia

El paquete incluye sus migraciones en `src/Migrations/`. Se distribuyen como **migraciones Doctrine versionadas** con un namespace propio: `Letkode\FormSchema\Migrations`.

### 15.2 Integración en el proyecto consumidor

El consumidor agrega el namespace en su `config/packages/doctrine_migrations.yaml`:

```yaml
doctrine_migrations:
    migrations_paths:
        'App\Migrations': '%kernel.project_dir%/migrations'
        'Letkode\FormSchema\Migrations': '@LetkodeFormSchemaBundle/Migrations'
```

Luego ejecuta normalmente:

```bash
php bin/console doctrine:migrations:migrate
```

### 15.3 Migración inicial

`Version20260101000000.php`: crea las 6 tablas con sus índices, FKs y columnas JSON. Se diseña para ser compatible con PG/MySQL/MariaDB usando `Doctrine\DBAL\Schema\Schema` (sin SQL manual cuando es posible).

### 15.4 Comando opcional

`bin/console letkode:form-schema:install` — wrapper que ejecuta solo las migraciones del paquete. Útil cuando el proyecto usa otra librería de migraciones.

---

## 17. Configuración del bundle

```yaml
# config/packages/letkode_form_schema.yaml
letkode_form_schema:
    # Locale por defecto si no se especifica en el resolver
    default_locale: 'es'

    # Locales disponibles (informativo, opcional)
    available_locales: ['es', 'en']

    # Namespace para resolver entities en options_entity (default)
    entity_namespace: 'App\Entity'

    # ─── Nombres de tabla ──────────────────────────────────────────────────────
    # table_prefix: prefijo aplicado a todas las tablas del bundle.
    # table_names:  override fino por entidad (el prefijo NO se aplica a
    #               entradas definidas aquí — lo que escribes es el nombre final).
    #
    # Resolución: table_names[entity] ?? (table_prefix + nombre_por_defecto)
    #
    # Nombres por defecto (sin prefijo):
    #   form, form_section, form_group, form_field,
    #   form_option_general, form_option_general_value
    #
    # Ejemplos:
    #   table_prefix: 'app_'               → app_form, app_form_section, …
    #   table_names: { form: 'my_forms' }  → my_forms (el resto usa el prefijo)
    table_prefix: ''
    table_names:
        form: ~                            # null → usa prefijo + nombre por defecto
        form_section: ~
        form_group: ~
        form_field: ~
        form_option_general: ~
        form_option_general_value: ~

    # Cache PSR-6
    cache:
        enabled: false
        pool: cache.app
        ttl: 3600
        key_prefix: 'fsb'
        auto_invalidate: true          # registra subscriber Doctrine

    # Field types deshabilitados (si el proyecto no quiere alguno built-in)
    disabled_field_types: []

    # Options sources deshabilitados
    disabled_options_sources: []

    # Renders deshabilitados por nivel
    disabled_form_renders: []
    disabled_section_renders: []
    disabled_group_renders: []
```

### 16.1 Bundle skeleton (AbstractBundle, estilo Symfony 7)

Siguiendo el mismo patrón que `letkode/helpers`, **sin compiler passes** (los registries reciben sus servicios vía `#[AutowireIterator]`):

```php
<?php
declare(strict_types=1);

namespace Letkode\FormSchema;

use Letkode\FormSchema\Domain\Contract\{
    FieldTypeInterface, FormRenderInterface, GroupRenderInterface,
    OptionsSourceInterface, SectionRenderInterface,
};
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class LetkodeFormSchemaBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('default_locale')->defaultValue('es')->end()
                ->arrayNode('available_locales')->scalarPrototype()->end()->defaultValue(['es'])->end()
                ->scalarNode('entity_namespace')->defaultValue('App\\Entity')->end()
                // Nombres de tabla: prefijo base + overrides finos por entidad
                ->scalarNode('table_prefix')->defaultValue('')->end()
                ->arrayNode('table_names')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('form')->defaultNull()->end()
                        ->scalarNode('form_section')->defaultNull()->end()
                        ->scalarNode('form_group')->defaultNull()->end()
                        ->scalarNode('form_field')->defaultNull()->end()
                        ->scalarNode('form_option_general')->defaultNull()->end()
                        ->scalarNode('form_option_general_value')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('pool')->defaultValue('cache.app')->end()
                        ->integerNode('ttl')->defaultValue(3600)->end()
                        ->scalarNode('key_prefix')->defaultValue('fsb')->end()
                        ->booleanNode('auto_invalidate')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('disabled_field_types')->scalarPrototype()->end()->end()
                ->arrayNode('disabled_options_sources')->scalarPrototype()->end()->end()
                ->arrayNode('disabled_form_renders')->scalarPrototype()->end()->end()
                ->arrayNode('disabled_section_renders')->scalarPrototype()->end()->end()
                ->arrayNode('disabled_group_renders')->scalarPrototype()->end()->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import($this->getPath() . '/src/Resources/config/services.yaml');

        $builder->setParameter('letkode_form_schema.default_locale', $config['default_locale']);
        $builder->setParameter('letkode_form_schema.entity_namespace', $config['entity_namespace']);
        $builder->setParameter('letkode_form_schema.cache', $config['cache']);
        // ... resto de parámetros

        // Auto-tag interfaces para que sean inyectables vía #[AutowireIterator] en los registries
        $builder->registerForAutoconfiguration(FieldTypeInterface::class)
            ->addTag('form_schema.field_type');
        $builder->registerForAutoconfiguration(OptionsSourceInterface::class)
            ->addTag('form_schema.options_source');
        $builder->registerForAutoconfiguration(FormRenderInterface::class)
            ->addTag('form_schema.form_render');
        $builder->registerForAutoconfiguration(SectionRenderInterface::class)
            ->addTag('form_schema.section_render');
        $builder->registerForAutoconfiguration(GroupRenderInterface::class)
            ->addTag('form_schema.group_render');
    }
}
```

> **Sin `build()` y sin compiler passes**. El acoplamiento entre tags y registries lo resuelve `#[AutowireIterator]` directamente en el constructor de cada registry. Menos archivos, mismo comportamiento.

---

### 16.2 Resolución de nombres de tabla (`TableNameSubscriber`)

El bundle permite al proyecto consumidor personalizar el nombre de las tablas de las 6 entidades mediante dos keys de config complementarias: `table_prefix` y `table_names`.

**Regla de resolución** (en orden de prioridad):

```
nombre_final = table_names[entity_key] ?? (table_prefix . nombre_por_defecto)
```

| `entity_key`             | Nombre por defecto           |
|--------------------------|------------------------------|
| `form`                   | `form`                       |
| `form_section`           | `form_section`               |
| `form_group`             | `form_group`                 |
| `form_field`             | `form_field`                 |
| `form_option_general`    | `form_option_general`        |
| `form_option_general_value` | `form_option_general_value` |

> `table_names` entradas devuelven el nombre **final** (el prefijo no se aplica encima de ellas).

**Implementación** — `TableNameSubscriber` escucha el evento `loadClassMetadata` de Doctrine:

```php
<?php
declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Letkode\FormSchema\Domain\Entity\{
    Form, FormField, FormGroup, FormOptionGeneral, FormOptionGeneralValue, FormSection,
};

final class TableNameSubscriber
{
    /** @var array<class-string, string> tabla por defecto por clase */
    private const DEFAULT_TABLES = [
        Form::class                   => 'form',
        FormSection::class            => 'form_section',
        FormGroup::class              => 'form_group',
        FormField::class              => 'form_field',
        FormOptionGeneral::class      => 'form_option_general',
        FormOptionGeneralValue::class => 'form_option_general_value',
    ];

    /** @var array<class-string, string> tabla resuelta tras aplicar config */
    private array $resolvedNames;

    /**
     * @param string                $prefix     valor de config `table_prefix`
     * @param array<string, string|null> $names  valor de config `table_names`
     */
    public function __construct(string $prefix, array $names)
    {
        // Mapa key → class-string para la tabla_names config
        $keyToClass = array_flip(array_map(
            fn(string $fqcn) => $this->toKey($fqcn),
            array_keys(self::DEFAULT_TABLES),
        ));

        $this->resolvedNames = [];
        foreach (self::DEFAULT_TABLES as $class => $default) {
            $key = $this->toKey($class);
            $this->resolvedNames[$class] = $names[$key] ?? ($prefix . $default);
        }
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $meta = $args->getClassMetadata();
        $class = $meta->getName();

        if (!isset($this->resolvedNames[$class])) {
            return;
        }

        $meta->setPrimaryTable(['name' => $this->resolvedNames[$class]]);
    }

    private function toKey(string $fqcn): string
    {
        // "Letkode\FormSchema\Domain\Entity\FormOptionGeneralValue" → "form_option_general_value"
        $short = substr($fqcn, strrpos($fqcn, '\\') + 1);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $short));
    }
}
```

**Registro en `loadExtension()`** — el subscriber se registra siempre (incluso sin prefijo/names) de forma que la config por defecto produce los nombres exactos del `#[ORM\Table]` de cada entidad:

```php
// En LetkodeFormSchemaBundle::loadExtension()
$container->services()
    ->set(TableNameSubscriber::class)
        ->args([$config['table_prefix'], $config['table_names']])
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);
```

> **Por qué `loadClassMetadata` y no atributos en la entidad**: los atributos PHP son estáticos (compile-time). `loadClassMetadata` es el único hook oficial de Doctrine que permite sobrescribir el nombre de tabla en runtime sin parchear las entidades ni generar proxies customizados.

**Ejemplos de config en el proyecto consumidor:**

```yaml
# Solo prefijo (todas las tablas reciben el prefijo)
letkode_form_schema:
    table_prefix: 'app_'
    # Resultado: app_form, app_form_section, app_form_group,
    #            app_form_field, app_form_option_general, app_form_option_general_value

# Solo override fino (el resto queda sin prefijo)
letkode_form_schema:
    table_names:
        form: 'my_forms'
        form_field: 'my_fields'
    # Resultado: my_forms, form_section, form_group, my_fields, …

# Ambas combinadas (override fino gana sobre el prefijo)
letkode_form_schema:
    table_prefix: 'app_'
    table_names:
        form: 'core_forms'          # nombre final: core_forms (sin prefijo)
    # Resultado: core_forms, app_form_section, app_form_group, app_form_field, …
```

---

## 18. Puntos de extensión desde el proyecto

| Quiero... | Cómo |
|---|---|
| Agregar un tipo de campo nuevo | Implementar `FieldTypeInterface`, marcar con `#[AsFieldType]`. Autowire lo registra. |
| Sobrescribir un tipo built-in | Implementar el tipo con el mismo `getName()` y `priority` mayor en el atributo. |
| Agregar una fuente de opciones custom | Implementar `OptionsSourceInterface`, marcar con `#[AsOptionsSource]`. |
| Habilitar un repositorio como fuente de opciones entity | Marcar con `#[AsFormOptionsProvider]` (repetible por método). |
| Agregar un render a nivel Form (ej. kanban) | Implementar `FormRenderInterface`, marcar con `#[AsFormRender]`. |
| Agregar un render a nivel Section | Implementar `SectionRenderInterface`, marcar con `#[AsSectionRender]`. |
| Agregar un render a nivel Group | Implementar `GroupRenderInterface`, marcar con `#[AsGroupRender]`. |
| Agregar atributos custom por field | Agregar la clave directamente en el JSON de `attributes` en BD — queda al mismo nivel que los built-in. Acceder con `$attrs->get('mi_clave')`. |
| Personalizar nombres de tabla | Usar `table_prefix` (aplica a todas) y/o `table_names` (override fino por entidad). Ver sección 16.2. |
| Usar un atributo custom como contexto de filtrado | `->withContext('mi_clave')` — el resolver evalúa `attributes.mi_clave`. Lanza `UnknownContextException` si no existe. |
| Ampliar parámetros de Form/Section/Group/Field | Usar la clave `dynamic` interna de cada VO de parámetros — transparente desde fuera, accesible con `->get('mi_clave')`. |
| Invalidar cache al editar un Form | Auto vía `auto_invalidate: true` o manual vía `FormSchemaCacheInvalidator`. |
| Tener mis propias entidades extendiendo las del paquete | Las entidades NO son `final`; pueden extenderse y mapearse con `MappedSuperclass` si se requiere. |

---

## 19. Ejemplos de uso

### 18.1 Crear un formulario desde el proyecto (programático, sintaxis PHP 8.4)

```php
use Letkode\FormSchema\Domain\Entity\{Form, FormSection, FormGroup, FormField};
use Letkode\FormSchema\Domain\Enum\FieldTypeEnum;

// new sin paréntesis extra + chained access (PHP 8.4)
$form = new Form()
    ->setName('Contact Form')
    ->setTag('contact_form')
    ->setEnabled(true);

// traducciones con estructura {locale: {field: value}}
$form->setTranslation('en', 'name', 'Contact Form');

$section = new FormSection()
    ->setName('Información personal')
    ->setTag('personal_info')
    ->setPosition(1);
$form->addSection($section);

$group = new FormGroup()
    ->setName('Datos básicos')
    ->setTag('basic_data')
    ->setPosition(1);
$section->addGroup($group);

$emailField = new FormField()
    ->setName('Email')
    ->setTag('email')
    ->setType(FieldTypeEnum::EMAIL->value)
    ->setAttributes([
        'required' => true,
        'unique'   => ['enabled' => true, 'entity' => 'App\\Entity\\User', 'method' => 'findOneByEmail'],
        'create'   => true,
        'edit'     => false,
    ])
    ->setParameters([
        'placeholder' => 'mail@example.com',
        'style' => ['lg:w-6/12'],
    ])
    ->setPosition(1);
$group->addField($emailField);

// Asymmetric visibility: $form->id, $form->uuid, $form->tag son legibles directamente
echo $form->tag;        // 'contact_form'  — sin getter
echo $form->name;       // property hook devuelve el valor traducido al locale activo

$em->persist($form);
$em->flush();
```

### 18.2 Leer un formulario desde un controller (con locale del usuario)

```php
final class FormController
{
    public function __construct(
        private readonly FormSchemaResolverInterface $resolver,
    ) {}

    #[Route('/forms/{tag}', name: 'form_show', methods: ['GET'])]
    public function show(string $tag, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        // El proyecto decide la fuente del locale: usuario, request, header, query param
        $locale = $user?->getPreferredLocale()
            ?? $request->query->get('locale')
            ?? $request->getLocale();

        $dto = $this->resolver
            ->schema($tag)
            ->withLocale($locale)
            ->withContext('create')                // excluye fields donde attrs.create === false
            ->includingSections(['personal', 'contact'])
            ->resolve();

        return new JsonResponse($dto);
    }
}
```

### 18.3 Agregar un tipo custom desde el proyecto

```php
// src/Form/FieldType/CurrencyFieldType.php
namespace App\Form\FieldType;

use Letkode\FormSchema\Attribute\AsFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\AbstractFieldType;
use Letkode\FormSchema\Domain\ValueObject\FieldAttributes;

#[AsFieldType]
final class CurrencyFieldType extends AbstractFieldType
{
    public static function getName(): string { return 'currency'; }

    public function getDefaultAttributes(): FieldAttributes
    {
        return FieldAttributes::default()
            ->withExtra('currency_code', 'CLP')
            ->withExtra('decimals', 0);
    }

    public function formatDefaultValue(mixed $rawValue): mixed
    {
        return is_numeric($rawValue) ? (float) $rawValue : 0.0;
    }
}
```

Sin tocar `services.yaml`. Autowire + `registerForAutoconfiguration` + `#[AutowireIterator]` en el registry lo enganchan.

### 18.4 Agregar una fuente de opciones custom

```php
#[AsOptionsSource]
final class OptionsApiSource implements OptionsSourceInterface
{
    public function __construct(private HttpClientInterface $http) {}
    public static function getName(): string { return 'api'; }

    public function resolve(array $parameters, ?string $locale = null): array
    {
        $url = $parameters['url'] ?? throw new \InvalidArgumentException('url required');
        $data = $this->http->request('GET', $url)->toArray();
        return array_map(
            fn($row) => new OptionDTO(value: $row['id'], text: $row['name']),
            $data['items'],
        );
    }
}
```

Y en BD el field tiene:
```json
{"set_options": [{"type": "api", "params": {"url": "https://my-api/options"}}]}
```

### 18.5 Formulario tipo stepper con 3 pasos

Configurar un formulario de onboarding multi-paso (stepper a nivel Form, sus secciones son los pasos):

```php
$form = new Form()
    ->setName('Onboarding')
    ->setTag('user_onboarding')
    ->setParameters([
        'type_render' => 'stepper',                         // ← FormRender
        'stepper' => [
            'navigation' => 'horizontal',
            'show_progress' => true,
            'allow_skip' => false,
            'persist_on_navigate' => true,
        ],
    ]);

$step1 = new FormSection()
    ->setName('Datos personales')
    ->setTag('personal')
    ->setPosition(1)
    ->setParameters(['type_render' => 'simple']);
$form->addSection($step1);

$step2 = new FormSection()
    ->setName('Información de contacto')
    ->setTag('contact')
    ->setPosition(2)
    ->setParameters([
        'type_render' => 'accordion',                       // ← SectionRender
        'accordion' => ['first_open' => true],
    ]);
$form->addSection($step2);

$step3 = new FormSection()
    ->setName('Preferencias')
    ->setTag('preferences')
    ->setPosition(3)
    ->setParameters([
        'type_render' => 'tabs',                            // ← SectionRender (alternativo)
    ]);
$form->addSection($step3);

// ... addGroups / addFields a cada step
$em->persist($form);
$em->flush();
```

Cuando se resuelve, el DTO viene así (resumido):

```json
{
  "id": "...",
  "tag": "user_onboarding",
  "name": "Onboarding",
  "render": {
    "type": "stepper",
    "metadata": {
      "navigation": "horizontal",
      "show_progress": true,
      "allow_skip": false,
      "persist_on_navigate": true
    }
  },
  "sections": [
    {
      "tag": "personal",
      "render": { "type": "simple", "metadata": {} },
      "groups": [/* ... */]
    },
    {
      "tag": "contact",
      "render": { "type": "accordion", "metadata": {"allow_multiple_open": false, "first_open": true} },
      "groups": [/* ... */]
    },
    {
      "tag": "preferences",
      "render": { "type": "tabs", "metadata": {"position": "top", "lazy_load": false} },
      "groups": [/* ... */]
    }
  ]
}
```

El frontend interpreta `form.render.type === 'stepper'` y arma la UI con la secuencia `form.sections[]` como pasos.

---

## 20. Lista de tareas / Plan de implementación por fases

### Fase 1 — Core (MVP funcional)
1. Setup composer.json, bundle skeleton, services.yaml, DI extension/configuration.
2. Entidades + traits (`HasTranslationsTrait`, `HasUuidTrait`, `HasParametersTrait`, `HasTimestampsTrait`).
3. Migración inicial.
4. `FieldTypeEnum` + 18 `FieldType` implementations con `AbstractFieldType`.
5. `FieldTypeRegistry` (con `#[AutowireIterator]`) + `#[AsFieldType]` + `registerForAutoconfiguration`.
6. ValueObjects (`FieldAttributes`, `FieldParameters`, `*Parameters`).
7. DTOs.
8. `FormSchemaResolver` + filtros + context.
9. Repositories (interfaces + implementaciones Doctrine).

### Fase 2 — Opciones
10. `OptionsSourceInterface` + `OptionsSourceRegistry` (con `#[AutowireIterator]`) + `#[AsOptionsSource]` + `registerForAutoconfiguration`.
11. `OptionsGeneralSource` + entidades `FormOptionGeneral` / `FormOptionGeneralValue`.
12. `OptionsEntitySource` con `findForFormOptions` como método default.
13. `OptionsResolver` integrado en `FormSchemaResolver`.

### Fase 3 — Cache
14. `CachedFormSchemaResolver` decorator + service definition condicional según config.
15. `FormSchemaCacheInvalidator` + tag-aware adapter.
16. `DoctrineCacheInvalidationSubscriber` (opt-in).

### Fase 4 — DX y docs
17. README completo con quickstart + recipe book.
18. `letkode:form-schema:install` command.
19. Tests unitarios (≥80% cobertura del core).
20. Tests de integración con Doctrine + sqlite + pgsql.
21. CI con GitHub Actions: matriz PHP 8.4 / Symfony 6.4 / 7.x / PG / MySQL / sqlite.

### Fase 5 — Pulido
22. Documentar puntos de extensión avanzados (Opción B de atributos via decorador).
23. Static analysis (PHPStan level 8, Psalm opcional).
24. CS fixer (PER-CS).
25. Versión 1.0.0.

---

## 21. composer.json (boceto)

```json
{
    "name": "letkode/form-schema",
    "description": "Symfony Bundle for dynamic, database-driven form schemas with extensible field types and option sources.",
    "license": "MIT",
    "type": "symfony-bundle",
    "authors": [
        {"name": "LetKode App", "email": "letkode.app@gmail.com", "homepage": "https://letkode.com/"}
    ],
    "require": {
        "php": "^8.4",
        "ext-intl": "*",
        "doctrine/orm": "^3.4",
        "doctrine/dbal": "^4.0",
        "doctrine/doctrine-bundle": "^2.12",
        "doctrine/doctrine-migrations-bundle": "^3.3",
        "symfony/framework-bundle": "^7.0",
        "symfony/dependency-injection": "^7.0",
        "symfony/http-kernel": "^7.0",
        "symfony/uid": "^7.0",
        "symfony/cache": "^7.0",
        "psr/cache": "^3.0",
        "stof/doctrine-extensions-bundle": "^1.12",
        "gedmo/doctrine-extensions": "^3.15",
        "letkode/helpers": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.4",
        "phpstan/phpstan": "^2.0",
        "phpstan/phpstan-symfony": "^2.0",
        "phpstan/phpstan-doctrine": "^2.0",
        "friendsofphp/php-cs-fixer": "^3.95",
        "symfony/var-dumper": "^7.0",
        "doctrine/data-fixtures": "^2.0"
    },
    "autoload": {
        "psr-4": { "Letkode\\FormSchema\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "Letkode\\FormSchema\\Tests\\": "tests/" }
    },
    "extra": {
        "branch-alias": {
            "dev-main": "1.x-dev"
        },
        "symfony": {
            "bundles": {
                "Letkode\\FormSchema\\LetkodeFormSchemaBundle": ["all"]
            }
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## 22. Resumen ejecutivo

> Un **Symfony Bundle PHP 8.4 nativo** (estilo `AbstractBundle` de Symfony 7, Doctrine ORM ^3.4) que modela formularios en BD jerárquicos (Form→Section→Group→Field) con i18n dinámico por request, **18 tipos de campo predefinidos** extensibles vía Strategy + Registry (estilo `Doctrine\DBAL\Types\Type::addType()`), opciones dinámicas desde catálogo interno (`FormOptionGeneral`) o repositorios del proyecto (habilitados vía `#[AsFormOptionsProvider]`, método default `findForFormOptions`), **renders estructurales en 3 niveles** independientes y registrables (`FormRender`: simple/stepper/wizard/tabs/modal · `SectionRender`: simple/accordion/tabs/collapsible · `GroupRender`: simple/matrix/tabs), atributos como ValueObjects readonly con estructura plana en BD y soporte de claves custom para contextos de filtrado, DTOs readonly con `translations` completo como contrato de salida, migraciones distribuidas dentro del paquete, soft delete en todas las entidades, y cache PSR-6 opt-in con key basada en hash de todos los parámetros del builder. **Aprovecha intensivamente PHP 8.4**: property hooks (con backing fields privados para ORM), asymmetric visibility (`public private(set)`), `new` chaining, nuevas funciones de array (`array_find`/`array_any`/`array_all`), `#[\Override]` obligatorio, lazy objects nativos vía Doctrine 3.4. Reutiliza `letkode/helpers` para validators, conversores y excepciones. El proyecto consumidor instala vía Composer, agrega el namespace de migraciones, ejecuta `migrate`, y ya puede crear formularios programáticamente y consumirlos con `FormSchemaResolver->schema($tag)->withLocale($locale)->resolve()`.

---

## 23. Decisiones resueltas

| # | Tema | Decisión |
|---|---|---|
| 1 | Soft delete | **Sí**, en las 6 entidades. Filtro `softdeleteable` activado por defecto en el bundle. |
| 2 | Locale | Opción A: locale enviado en cada request vía `withLocale()`, backend resuelve y devuelve DTO traducido. DTOs incluyen `translations` completo `{locale: {field: value}}` para que el frontend maneje presentación sin re-request. Cadena de fallback: `locale solicitado → Form.defaultLang → valor crudo`. |
| 3 | Validación server-side | Fuera de v1. La prioridad es mantener clara y estable la estructura del schema. |
| 4 | Renders estructurales | **3 niveles** con registry independiente cada uno: `FormRender` (built-in: simple/stepper/wizard/tabs/modal), `SectionRender` (simple/accordion/tabs/collapsible), `GroupRender` (simple/matrix/tabs). Cada nivel extensible con su atributo `#[AsXxxRender]`. |
| 5 | Default method `OptionsEntitySource` | `findForFormOptions(array $context): array` |
| 6 | Bundle skeleton | `AbstractBundle` de Symfony 7 (igual que `letkode/helpers`). Sin Extension/Configuration separados. |
| 7 | Dependencia `letkode/helpers` | Sí, integrada para validators, conversores y excepciones. Evita duplicación. |
| 8 | PHP 8.4 features | Uso intensivo: property hooks (con backing field privado para ORM), asymmetric visibility (`public private(set)`), `new` chaining, `array_find` / `array_any` / `array_all`, `#[\Override]` obligatorio, `#[\Deprecated]` para evolución, lazy objects nativos vía Doctrine 3.4. |
| 9 | Doctrine ORM | **^3.4** (versión que oficialmente soporta property hooks y lazy objects de PHP 8.4). |

---

## 24. Definition of Done — v1.0.0

- [ ] Bundle se instala con `composer require letkode/form-schema` y se auto-registra vía `extra.symfony.bundles`.
- [ ] `bin/console doctrine:migrations:migrate` crea las 6 tablas correctamente en PostgreSQL y MySQL.
- [ ] Los 18 FieldTypes built-in están implementados y testeados.
- [ ] Las 2 OptionsSource built-in (`general`, `entity`) funcionan.
- [ ] Los **5 FormRender built-in** (`simple`, `stepper`, `wizard`, `tabs`, `modal`) funcionan.
- [ ] Los **4 SectionRender built-in** (`simple`, `accordion`, `tabs`, `collapsible`) funcionan.
- [ ] Los 3 GroupRender built-in (`simple`, `matrix`, `tabs`) funcionan.
- [ ] Un proyecto puede registrar un FieldType custom solo con `#[AsFieldType]`, sin tocar config.
- [ ] Un proyecto puede registrar una OptionsSource custom solo con `#[AsOptionsSource]`.
- [ ] Un proyecto puede registrar FormRender / SectionRender / GroupRender custom solo con su atributo correspondiente.
- [ ] `FormSchemaResolver::schema($tag)->withLocale($locale)->resolve()` devuelve `FormDTO` válido con los 3 `renderMeta` populados y `translations` completo en cada nivel.
- [ ] El locale fallback funciona en los 3 niveles documentados.
- [ ] Cache opt-in funciona end-to-end con invalidación por tag.
- [ ] Soft delete oculta automáticamente registros borrados en queries del paquete.
- [ ] Las entidades usan **asymmetric visibility** y **property hooks** correctamente (sin romper la hidratación Doctrine).
- [ ] Todos los overrides usan `#[\Override]`.
- [ ] README con quickstart (instalar → migrar → crear form programáticamente → consumir con stepper).
- [ ] PHPStan level 8 sin errores.
- [ ] PHPUnit ≥80% coverage del core (Resolver, Registries, FieldTypes, OptionsSources, los 3 Render Registries).