# Fuentes de opciones

Las opciones de los campos de tipo `list`, `list-group`, `multilist`, `radio`, `checkbox` y `rating` se resuelven en runtime a partir de una fuente declarada en `set_options` del campo.

El bundle incluye dos fuentes built-in.

---

## `general` — Catálogo interno de BD

Usa la entidad `FormOptionGeneral` / `FormOptionGeneralValue` para almacenar catálogos reutilizables dentro del mismo bundle.

### Cuándo usarla

- Opciones estáticas o semi-estáticas gestionadas desde un panel de administración.
- Listas que se comparten entre varios formularios (países, estados, monedas…).
- Opciones con traducciones propias por locale.

### Configuración en `set_options`

```json
{
  "type": "general",
  "params": {
    "tag": "countries",
    "with_all_option": false,
    "id_column": "id",
    "text_column": "name"
  }
}
```

| Parámetro | Requerido | Default | Descripción |
|---|---|---|---|
| `tag` | Sí | — | Identificador del catálogo (`FormOptionGeneral.tag`) |
| `with_all_option` | No | `false` | Agrega una opción "Todos" al inicio |
| `id_column` | No | `"id"` | Clave que se usa como `value` en `OptionDTO` |
| `text_column` | No | `"name"` | Clave que se usa como `label` en `OptionDTO` |

### Estructura de datos en BD

```
FormOptionGeneral
  ├── tag: 'countries'
  ├── name: 'Países'
  ├── enabled: true
  └── values: FormOptionGeneralValue[]
        ├── { id: 1, value: 'ES', name: 'España', position: 1, enabled: true }
        ├── { id: 2, value: 'MX', name: 'México', position: 2, enabled: true }
        └── …
```

Las traducciones de los valores se resuelven usando `getTranslation($locale, 'name')`.

---

## `entity` — Repositorios del proyecto

Permite usar cualquier repositorio del proyecto como fuente de opciones, con control explícito de qué clases y métodos están habilitados.

### Cuándo usarla

- Opciones que ya existen como entidades del proyecto (categorías, usuarios, clientes…).
- Datos que cambian con frecuencia y viven en tablas propias.
- Lógica de filtrado compleja (por tenant, por estado, etc.).

### Habilitar un repositorio

Marca el repositorio con `#[AsFormOptionsProvider]`. Es **repetible** — puedes declarar varios métodos:

```php
use Letkode\FormSchema\Attribute\AsFormOptionsProvider;

#[AsFormOptionsProvider]                          // método por defecto: findForFormOptions
#[AsFormOptionsProvider(method: 'findActive')]    // método adicional habilitado
class CategoryRepository extends ServiceEntityRepository
{
    public function findForFormOptions(array $params, ?string $locale = null): array
    {
        // Devuelve array de OptionDTO o array de ['value' => ..., 'label' => ...]
        return $this->createQueryBuilder('c')
            ->where('c.enabled = true')
            ->orderBy('c.name')
            ->getQuery()
            ->getArrayResult();
    }

    public function findActive(array $params, ?string $locale = null): array
    {
        // ...
    }
}
```

> El bundle solo llama métodos explícitamente declarados con `#[AsFormOptionsProvider]`. Llamar un método no declarado lanza `UnauthorizedOptionsMethodException`.

### Configuración en `set_options`

```json
{
  "type": "entity",
  "params": {
    "class": "App\\Repository\\CategoryRepository",
    "method": "findForFormOptions",
    "locale": "es"
  }
}
```

| Parámetro | Requerido | Default | Descripción |
|---|---|---|---|
| `class` | Sí | — | FQCN del repositorio |
| `method` | No | `"findForFormOptions"` | Método a invocar (debe estar en la whitelist) |

El `locale` activo del resolver se pasa automáticamente como segundo argumento al método. El parámetro `locale` en `params` es un fallback estático si el resolver no tiene locale configurado.

### Firma esperada del método

```php
public function findForFormOptions(array $params, ?string $locale = null): array
```

El retorno puede ser:
- `list<OptionDTO>` directamente
- `list<array{value: mixed, label: string}>` — el bundle normaliza
- `list<array{value: mixed, label: string, group?: string, ...}>` — para `list-group`

---

## DTO de opciones

```php
OptionDTO
  ├── value: mixed     ← valor que se envía al backend en submit
  ├── label: string    ← texto visible para el usuario
  ├── group: ?string   ← para list-group (nombre del grupo)
  └── meta: array      ← datos adicionales (icon, color, disabled…)
```

---

## Agregar una fuente de opciones propia

Ver [Extensibilidad](extending.md#fuentes-de-opciones).
