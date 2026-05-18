# FormSchemaResolver

El `FormSchemaResolver` es el servicio central del bundle. Implementa un **fluent builder inmutable**: cada método de configuración devuelve un clone del resolver sin mutar el original, y toda la lógica se ejecuta en `resolve()`.

## Inyección

```php
use Letkode\FormSchema\Domain\Contract\FormSchemaResolverInterface;

class MyController
{
    public function __construct(
        private readonly FormSchemaResolverInterface $resolver,
    ) {}
}
```

> Inyecta siempre la interfaz, no la implementación concreta. Cuando la caché está activa el contenedor inyecta automáticamente el decorator `CachedFormSchemaResolver`.

## API del builder

```php
$resolver
    ->schema(string $tag)              // REQUERIDO — identifica el formulario
    ->withLocale(string $locale)       // locale activo para traducciones
    ->withContext(string $context)     // filtra campos según atributo (ej. 'create', 'edit')
    ->includingSections(array $tags)   // solo estas secciones
    ->excludingSections(array $tags)   // todas menos estas secciones
    ->includingGroups(array $tags)     // solo estos grupos (dentro de las secciones permitidas)
    ->excludingGroups(array $tags)     // todos menos estos grupos
    ->resolve()                        // ejecuta y devuelve FormDTO
    // — o bien —
    ->toArray()                        // resolve() + jsonSerialize()
```

Todos los métodos son **inmutables**: devuelven un nuevo clone, el resolver original no cambia. Puedes reutilizarlo como base:

```php
$base = $resolver->schema('checkout')->withLocale('en');

$forCreate = $base->withContext('create')->resolve();
$forEdit   = $base->withContext('edit')->resolve();
```

## Ejemplos de uso

### Básico

```php
$form = $resolver->schema('user_profile')->resolve();
```

### Con locale del request

```php
$locale = $request->getLocale(); // 'en', 'es', 'pt'…
$form   = $resolver->schema('user_profile')->withLocale($locale)->resolve();
```

### Con contexto (filtrado de campos)

El contexto evalúa el atributo del campo con el mismo nombre. Si el atributo es `false`, el campo se omite del DTO. Si la clave no existe en los atributos, se lanza `UnknownContextException`.

```php
// Solo campos con attributes.create !== false
$form = $resolver->schema('product')->withContext('create')->resolve();

// Solo campos con attributes.edit !== false
$form = $resolver->schema('product')->withContext('edit')->resolve();
```

### Filtrando secciones y grupos

```php
// Solo la sección de datos personales
$form = $resolver
    ->schema('onboarding')
    ->withLocale('es')
    ->includingSections(['personal_data'])
    ->resolve();

// Todas las secciones excepto preferencias
$form = $resolver
    ->schema('onboarding')
    ->excludingSections(['preferences'])
    ->resolve();
```

### Como array (para JSON response)

```php
return $this->json(
    $resolver->schema('checkout')->withLocale($locale)->toArray()
);
```

## Estructura del FormDTO

```
FormDTO
  ├── id: string (UUIDv7)
  ├── name: string
  ├── tag: string
  ├── locale: string              ← locale activo en esta resolución
  ├── default_locale: ?string
  ├── enabled: bool
  ├── parameters: array
  ├── render: {type: string, metadata: array}   ← 'simple' | 'stepper' | 'wizard' | 'tabs'
  ├── translations: array|null    ← {locale: {field: value}} completo
  └── sections: SectionDTO[]
        ├── id, name, tag, description, position, enabled
        ├── parameters, render, translations
        └── groups: GroupDTO[]
              ├── id, name, tag, description, position, enabled
              ├── parameters, render, translations
              └── fields: FieldDTO[]
                    ├── id, name, tag, type, description
                    ├── attributes: array   ← FieldAttributes serializado
                    ├── parameters: array
                    ├── position, enabled
                    ├── placeholder: ?string
                    ├── default_value: mixed
                    ├── style: array
                    ├── options: OptionDTO[]
                    └── translations: array|null
```

### `render.metadata` por tipo de Form

| render.type | render.metadata contiene |
|---|---|
| `simple` | `{}` vacío |
| `stepper` | `show_progress`, `navigation`, `allow_skip`, `steps_orientation` |
| `wizard` | `show_steps`, `allow_back`, `finish_label` |
| `tabs` | `tab_position`, `lazy_load` |

## Excepciones

| Excepción | Cuándo se lanza |
|---|---|
| `FormNotFoundException` | No existe un formulario con el tag pedido |
| `UnknownContextException` | El contexto no existe como clave en los atributos del campo |
