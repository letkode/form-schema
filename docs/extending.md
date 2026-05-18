# Extensibilidad

El bundle está diseñado para ser extendido sin modificar su código fuente. El mecanismo es siempre el mismo: implementar la interfaz correspondiente, marcar la clase con el atributo PHP adecuado, y Symfony la registra automáticamente.

---

## Tipos de campo

### Crear un tipo nuevo

```php
use Letkode\FormSchema\Attribute\AsFieldType;
use Letkode\FormSchema\Domain\Contract\FieldTypeInterface;
use Letkode\FormSchema\Domain\ValueObject\FieldAttributes;
use Letkode\FormSchema\Infrastructure\FieldType\AbstractFieldType;

#[AsFieldType(name: 'color-picker')]
final class ColorPickerFieldType extends AbstractFieldType
{
    #[\Override]
    public function getDefaultAttributes(): FieldAttributes
    {
        return FieldAttributes::fromArray([
            'required' => false,
            'format'   => 'hex',   // atributo custom para este tipo
        ]);
    }

    #[\Override]
    public function formatDefaultValue(mixed $rawValue): mixed
    {
        // Normaliza el valor por defecto
        return is_string($rawValue) ? strtolower($rawValue) : null;
    }
}
```

No hay que declarar nada más. El tag `form_schema.field_type` se aplica automáticamente vía `registerForAutoconfiguration`.

### Sobrescribir un tipo built-in

Implementa el tipo con el mismo `name` y una prioridad mayor:

```php
#[AsFieldType(name: 'email', priority: 10)]   // built-in usa priority: 0
final class CustomEmailFieldType extends AbstractFieldType
{
    // tu lógica
}
```

### Deshabilitar un tipo built-in

```yaml
letkode_form_schema:
    disabled_field_types: ['rating', 'date-range']
```

---

## Fuentes de opciones

### Crear una fuente nueva

```php
use Letkode\FormSchema\Attribute\AsOptionsSource;
use Letkode\FormSchema\Domain\Contract\OptionsSourceInterface;

#[AsOptionsSource(name: 'graphql')]
final class GraphqlOptionsSource implements OptionsSourceInterface
{
    #[\Override]
    public function getName(): string
    {
        return 'graphql';
    }

    #[\Override]
    public function resolve(array $params, ?string $locale = null): array
    {
        // Consulta a una API GraphQL, devuelve array de OptionDTO
        // o array de ['value' => ..., 'label' => ...]
        return [];
    }
}
```

Luego en `set_options` del campo:

```json
{
  "type": "graphql",
  "params": {
    "query": "...",
    "endpoint": "https://api.example.com/graphql"
  }
}
```

### Habilitar un repositorio del proyecto como fuente `entity`

```php
use Letkode\FormSchema\Attribute\AsFormOptionsProvider;

// Método por defecto (findForFormOptions)
#[AsFormOptionsProvider]
class ProductRepository extends ServiceEntityRepository
{
    public function findForFormOptions(array $params, ?string $locale = null): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id AS value, p.name AS label')
            ->where('p.enabled = true')
            ->getQuery()
            ->getArrayResult();
    }
}

// Múltiples métodos en la misma clase
#[AsFormOptionsProvider(method: 'findActive')]
#[AsFormOptionsProvider(method: 'findByCategory')]
class CategoryRepository extends ServiceEntityRepository
{
    public function findActive(array $params, ?string $locale = null): array { /* … */ }
    public function findByCategory(array $params, ?string $locale = null): array { /* … */ }
}
```

El atributo `#[AsFormOptionsProvider]` es **repetible** — cada instancia habilita un método. Solo los métodos declarados pueden ser invocados; intentar llamar a otro lanza `UnauthorizedOptionsMethodException`.

---

## Renders

### Crear un render de Form

```php
use Letkode\FormSchema\Attribute\AsFormRender;
use Letkode\FormSchema\Domain\Contract\FormRenderInterface;

#[AsFormRender(name: 'kanban')]
final class KanbanFormRender implements FormRenderInterface
{
    #[\Override]
    public function getName(): string
    {
        return 'kanban';
    }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'column_field' => $parameters['column_field'] ?? 'status',
            'swimlane'     => $parameters['swimlane'] ?? false,
        ];
    }
}
```

### Crear un render de Section

```php
use Letkode\FormSchema\Attribute\AsSectionRender;
use Letkode\FormSchema\Domain\Contract\SectionRenderInterface;

#[AsSectionRender(name: 'carousel')]
final class CarouselSectionRender implements SectionRenderInterface
{
    #[\Override]
    public function getName(): string { return 'carousel'; }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'autoplay'  => $parameters['autoplay'] ?? false,
            'interval'  => $parameters['interval'] ?? 5000,
        ];
    }
}
```

### Crear un render de Group

```php
use Letkode\FormSchema\Attribute\AsGroupRender;
use Letkode\FormSchema\Domain\Contract\GroupRenderInterface;

#[AsGroupRender(name: 'masonry')]
final class MasonryGroupRender implements GroupRenderInterface
{
    #[\Override]
    public function getName(): string { return 'masonry'; }

    #[\Override]
    public function renderMeta(array $parameters): array
    {
        return [
            'min_column_width' => $parameters['min_column_width'] ?? 200,
            'gap'              => $parameters['gap'] ?? 16,
        ];
    }
}
```

> Los renders de los 3 niveles son interfaces **separadas e incompatibles** para que Symfony los tag-ee correctamente y un `FormRender` no pueda usarse accidentalmente como `GroupRender`.

---

## Resumen de atributos de extensión

| Atributo | Interfaz | Registra en |
|---|---|---|
| `#[AsFieldType]` | `FieldTypeInterface` | `FieldTypeRegistry` |
| `#[AsOptionsSource]` | `OptionsSourceInterface` | `OptionsSourceRegistry` |
| `#[AsFormRender]` | `FormRenderInterface` | `FormRenderRegistry` |
| `#[AsSectionRender]` | `SectionRenderInterface` | `SectionRenderRegistry` |
| `#[AsGroupRender]` | `GroupRenderInterface` | `GroupRenderRegistry` |
| `#[AsFormOptionsProvider]` | — (atributo en repositorios) | `OptionsEntitySource` whitelist |
