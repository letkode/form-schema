# Tipos de campo

El bundle incluye 18 tipos de campo predefinidos. Todos implementan `FieldTypeInterface` y son auto-descubiertos vía `#[AsFieldType]` + `registerForAutoconfiguration`.

## Tipos disponibles

| Tipo | Descripción | Toma opciones |
|---|---|---|
| `string` | Texto de una línea | No |
| `email` | Email con validación básica de formato | No |
| `phone` | Teléfono | No |
| `number` | Numérico (entero o decimal) | No |
| `password` | Contraseña (enmascarada) | No |
| `hidden` | Campo oculto, no renderizado | No |
| `textarea` | Texto multilínea | No |
| `date` | Fecha (ISO 8601) | No |
| `datetime` | Fecha y hora | No |
| `date-range` | Rango de fechas (`{from, to}`) | No |
| `file` | Carga de archivo | No |
| `switch` | Toggle booleano | No |
| `rating` | Valoración numérica | Sí |
| `list` | Selección simple de una opción | Sí |
| `list-group` | Selección simple agrupada | Sí |
| `multilist` | Selección múltiple | Sí |
| `radio` | Selección única estilo radio | Sí |
| `checkbox` | Selección múltiple estilo checkbox | Sí |

## Atributos del campo (`attributes`)

Los atributos se almacenan como JSON en BD y se resuelven como `FieldAttributes` (value object readonly). Se serializa plano en el DTO.

### Atributos built-in

| Atributo | Tipo | Default | Descripción |
|---|---|---|---|
| `required` | `bool` | `false` | Campo obligatorio |
| `readonly` | `bool` | `false` | No editable por el usuario |
| `show` | `bool` | `true` | Visible en contexto `show` |
| `edit` | `bool` | `true` | Visible en contexto `edit` |
| `create` | `bool` | `true` | Visible en contexto `create` |
| `unique` | `object` | ver abajo | Regla de unicidad |
| `filter` | `object` | ver abajo | Regla de filtrado en listados |

#### `unique`

```json
{
  "enabled": false,
  "scope": []
}
```

- `enabled`: activa la validación de unicidad
- `scope`: campos adicionales que forman el scope de unicidad (ej. `["tenant_id"]`)

#### `filter`

```json
{
  "enabled": false,
  "type": "exact",
  "operators": []
}
```

- `enabled`: el campo aparece como filtro disponible en listados
- `type`: tipo de filtro (`exact`, `range`, `like`, `in`)
- `operators`: operadores disponibles para el frontend

### Atributos dinámicos (custom context keys)

Cualquier clave adicional en el JSON de `attributes` se almacena en el array `$dynamic` interno y aparece al mismo nivel en `toArray()`. Esto permite definir contextos propios:

```json
{
  "required": true,
  "create": true,
  "edit": false,
  "approval": true
}
```

```php
// Filtra solo campos visibles en contexto 'approval'
$form = $resolver->schema('expense')->withContext('approval')->resolve();
```

Acceso programático:

```php
$attrs = FieldAttributes::fromArray($field->attributes);
$isApproval = $attrs->get('approval', false);
```

## Parámetros del campo (`parameters`)

Los parámetros son datos adicionales de configuración del campo almacenados como JSON. No forman parte del contrato de atributos. El DTO expone `parameters` excluyendo las claves internas que se sacan a propiedades dedicadas.

| Clave (en BD) | Expuesto en DTO como | Descripción |
|---|---|---|
| `placeholder` | `FieldDTO::$placeholder` | Texto de placeholder (fallback si no hay traducción) |
| `default_value` | `FieldDTO::$defaultValue` | Valor por defecto (procesado por el tipo) |
| `style` | `FieldDTO::$style` | CSS o clases para el frontend |
| `set_options` | (resuelto → `FieldDTO::$options`) | Configuración de la fuente de opciones |
| cualquier otra | `FieldDTO::$parameters` | Pasa directo al frontend |

### `set_options`

Objeto único que configura la fuente de opciones del campo:

```json
{
  "set_options": {
    "type": "general",
    "params": {
      "tag": "countries",
      "with_all_option": true
    }
  }
}
```

```json
{
  "set_options": {
    "type": "entity",
    "params": {
      "class": "App\\Entity\\Category",
      "method": "findForFormOptions",
      "locale": "es"
    }
  }
}
```

Ver [Fuentes de opciones](options-sources.md) para detalle de cada tipo.

## Extender con tipos propios

Ver [Extensibilidad](extending.md#tipos-de-campo).
