# Tipos de campo

El bundle incluye 21 tipos de campo predefinidos. Todos implementan `FieldTypeInterface` y son auto-descubiertos vía `#[AsFieldType]` + `registerForAutoconfiguration`.

## Tipos disponibles

| Tipo | Descripción | Toma opciones | FlyonUI |
|---|---|---|---|
| `string` | Texto de una línea | No | `input` |
| `email` | Email con validación básica de formato | No | `input type="email"` |
| `phone` | Teléfono | No | `input type="tel"` |
| `number` | Numérico (entero o decimal) | No | `data-input-number` |
| `password` | Contraseña con indicador de fortaleza | No | `data-strong-password` |
| `hidden` | Campo oculto, no renderizado | No | `input type="hidden"` |
| `textarea` | Texto multilínea | No | `textarea` |
| `date` | Fecha (ISO 8601) | No | Datepicker |
| `datetime` | Fecha y hora | No | Datepicker |
| `date-range` | Rango de fechas (`{from, to}`) | No | Range picker |
| `file` | Carga de archivo | No | `input type="file"` |
| `switch` | Toggle booleano | Sí | Toggle / Switch |
| `rating` | Valoración con iconos | Sí | Rating |
| `select` | Selección simple o múltiple | Sí | `data-select` (advanced-select) |
| `radio` | Selección única estilo radio | Sí | Radio group |
| `checkbox` | Selección múltiple estilo checkbox | Sí | Checkbox group |
| `range` | Slider numérico | No | `range` |
| `combobox` | Autocompletado con búsqueda | No | `data-combo-box` |
| `pin` | Entrada de PIN / OTP | No | `data-pin-input` |
| `tree` | Árbol jerárquico con selección | Sí | `data-tree-view` |
| `duallist` | Selector doble panel (disponibles/seleccionados) | Sí | Custom |

> **Tipos eliminados (migrar a `select`):** `list`, `list-group`, `multilist` — ver sección [Migración](#migración-list--list-group--multilist-→-select) abajo.

---

## UI Params por tipo (`getDefaultParams`)

Cada FieldType declara sus params UI con defaults. El resolver hace `array_merge($type->getDefaultParams(), $storedParams)` — DB siempre gana. El frontend siempre recibe todos los params con valores sensatos incluso si no están configurados en BD.

### `string` / `email` / `phone` / `hidden`

```json
{ "size": "md", "label_style": "default" }
```

### `textarea`

```json
{
  "size": "md",
  "label_style": "default",
  "icon_leading": null,
  "icon_trailing": null
}
```

### `number`

```json
{
  "size": "md",
  "label_style": "default",
  "min": null,
  "max": null,
  "step": 1,
  "button_layout": "default"
}
```

FlyonUI: `data-input-number`.

### `password`

```json
{
  "size": "md",
  "label_style": "default",
  "show_strength": true,
  "min_length": 8,
  "checks_exclude": []
}
```

FlyonUI: `data-strong-password` + toggle password.

### `date` / `datetime` / `date-range`

```json
{ "size": "md", "label_style": "default" }
```

### `file`

```json
{
  "size": "md",
  "label_style": "default",
  "multiple": false
}
```

### `switch`

```json
{
  "size": "md",
  "color": "primary",
  "variant": "solid",
  "layout": "vertical"
}
```

### `checkbox`

```json
{
  "size": "md",
  "color": "primary",
  "layout": "vertical"
}
```

### `radio`

```json
{
  "size": "md",
  "color": "primary",
  "variant": "default",
  "layout": "vertical"
}
```

### `rating`

```json
{
  "size": "md",
  "color": "primary",
  "max_stars": 5,
  "half_star": false
}
```

### `select`

```json
{
  "size": "md",
  "multiple": false,
  "searchable": false,
  "tags_mode": false,
  "search_limit": null,
  "min_search_length": null,
  "max_count_items": null
}
```

FlyonUI: `data-select` (advanced-select). `tags_mode: true` solo aplica cuando `multiple: true`.

### `range`

```json
{
  "size": "md",
  "color": "primary",
  "min": 0,
  "max": 100,
  "step": 1,
  "show_steps": false
}
```

FlyonUI: `<input type="range" class="range range-{color}">`. `formatDefaultValue` castea a `int`.

### `combobox`

```json
{
  "size": "md",
  "open_on_focus": false,
  "min_search_length": 0,
  "api_url": null
}
```

FlyonUI: `data-combo-box`. Si `api_url` está presente, el frontend carga opciones bajo demanda. Sin `api_url`, usa `field.options` del schema.

### `pin`

```json
{
  "size": "md",
  "variant": "outline",
  "length": 4,
  "input_type": "text",
  "chars_pattern": null
}
```

FlyonUI: `data-pin-input`. `variant`: `outline` | `underline`. `input_type`: `text` | `password` | `tel`. `formatDefaultValue` castea a `string`.

### `tree`

```json
{
  "selection_mode": "checkbox",
  "auto_select_children": true,
  "expanded_by_default": false
}
```

FlyonUI: `data-tree-view` con `controlBy: "checkbox"`. El modo `radio` (selección única) es gestionado por el frontend limitando la selección a un nodo. Ver [Estructura de opciones para `tree`](#opciones-para-tree).

### `duallist`

```json
{
  "size": "md",
  "searchable": true,
  "source_label": null,
  "target_label": null,
  "show_move_all_buttons": true
}
```

Custom dual-panel. `formatDefaultValue` retorna siempre un `array`: `[] | [$value] | $array`. El valor del campo es el array de `value`s en el panel "seleccionados".

---

## Atributos del campo (`attributes`)

Los atributos se almacenan como JSON en BD y se resuelven como `FieldAttributes` (value object readonly).

### Atributos built-in

| Atributo | Tipo | Default | Descripción |
|---|---|---|---|
| `required` | `bool` | `false` | Campo obligatorio |
| `readonly` | `bool` | `false` | No editable por el usuario |
| `show` | `bool` | `true` | Visible en contexto `show` |
| `edit` | `bool` | `true` | Visible en contexto `edit` |
| `create` | `bool` | `true` | Visible en contexto `create` |
| `unique` | `object` | `{enabled: false}` | Regla de unicidad |
| `filter` | `object` | `{enabled: false}` | Regla de filtrado en listados |

### Atributos dinámicos (custom context keys)

Cualquier clave adicional en el JSON se almacena en `$dynamic` y aparece al mismo nivel en `toArray()`:

```php
$form = $resolver->schema('expense')->withContext('approval')->resolve();
```

---

## Parámetros del campo (`parameters`)

Las claves reservadas se extraen a propiedades dedicadas del `FieldDTO`; el resto se merge con los defaults del tipo y se expone en `parameters`.

| Clave (en BD) | Expuesto en DTO como | Descripción |
|---|---|---|
| `placeholder` | `FieldDTO::$placeholder` | Texto de placeholder (resuelto por locale) |
| `default_value` | `FieldDTO::$defaultValue` | Valor por defecto (procesado por el tipo) |
| `style` | `FieldDTO::$style` | Clases CSS o config de layout |
| `set_options` | (resuelto → `FieldDTO::$options`) | Configuración de la fuente de opciones |
| cualquier otra | `FieldDTO::$parameters` | Mergeado con `getDefaultParams()` del tipo |

### `set_options`

```json
{
  "set_options": {
    "type": "general",
    "params": { "set": "countries", "with_all_option": true }
  }
}
```

Ver [Fuentes de opciones](options-sources.md).

---

## Opciones (`field.options`)

### Estructura de `OptionDTO`

```json
{
  "value": "cl",
  "text": "Chile",
  "tag": "cl",
  "icon": null,
  "color": null,
  "position": 1,
  "data": {}
}
```

El campo `data` (default `{}`) es el punto de extensibilidad de cada opción. Sirve para:

#### 1. Data-attrs para interactions

Pares clave-valor que el frontend aplica como `data-*` en el DOM, habilitando `filter_options` en modo `client`:

```json
{ "value": "rm", "text": "Región Metropolitana", "data": { "country_id": "cl", "group": "Centro" } }
```

#### 2. Agrupamiento visual para `select`

El frontend puede usar `data.group` para renderizar `<optgroup>` (equivalente al anterior `list-group`):

```json
[
  { "value": "rm", "text": "Región Metropolitana", "data": { "group": "Centro" } },
  { "value": "i",  "text": "Tarapacá",             "data": { "group": "Norte"  } }
]
```

### Opciones para `tree`

La jerarquía se define con `data.children` (recursivo). `data.is_dir` distingue nodos rama de hojas:

```json
{
  "value": "docs",
  "text": "Documentos",
  "icon": "tabler--folder",
  "data": {
    "is_dir": true,
    "children": [
      { "value": "docs_contracts", "text": "Contratos", "data": { "is_dir": false } },
      { "value": "docs_reports",   "text": "Reportes",  "data": { "is_dir": false } }
    ]
  }
}
```

---

## Migración: `list` / `list-group` / `multilist` → `select`

Los tres tipos legacy fueron unificados en `select`. Actualizar el campo `type` en los datos existentes:

| Tipo anterior | Equivalente en `select` |
|---|---|
| `list` | `type: "select"` (defaults aplican, `multiple: false`) |
| `list-group` | `type: "select"` + `data.group` en cada opción |
| `multilist` | `type: "select"` + `parameters.multiple: true` |

---

## Extender con tipos propios

Ver [Extensibilidad](extending.md#tipos-de-campo).
