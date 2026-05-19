# Renders

Los renders controlan la **estructura visual** del formulario en 3 niveles independientes. Cada nivel tiene su propio registro y no son intercambiables entre niveles.

El tipo de render se define en el campo `parameters.type_render` de cada entidad. La metadata generada por el render se incluye en el DTO dentro de la llave `render` y es consumida por el frontend.

---

## Renders de Form

Configuran la estructura global del formulario.

### `simple` (default)

Formulario plano sin navegación especial. Todas las secciones se renderizan seguidas.

```json
{ "render": { "type": "simple", "metadata": {} } }
```

### `stepper`

Cada `FormSection` es un **paso** del stepper. El frontend usa `form.sections[]` como secuencia de pasos.

```json
{
  "render": {
    "type": "stepper",
    "metadata": {
      "show_progress": true,
      "navigation": "bottom",
      "allow_skip": false,
      "steps_orientation": "horizontal"
    }
  }
}
```

| Parámetro en `parameters` | Descripción | Valores |
|---|---|---|
| `show_progress` | Muestra barra de progreso | `true` / `false` |
| `navigation` | Dónde van los botones Anterior/Siguiente | `"bottom"` / `"top"` / `"both"` |
| `allow_skip` | Permite saltar pasos opcionales | `true` / `false` |
| `steps_orientation` | Orientación de los indicadores de paso | `"horizontal"` / `"vertical"` |

### `wizard`

Similar a stepper pero con énfasis en confirmación final.

```json
{
  "render": {
    "type": "wizard",
    "metadata": {
      "show_steps": true,
      "allow_back": true,
      "finish_label": "Enviar"
    }
  }
}
```

### `tabs`

Las secciones se presentan como pestañas accesibles en cualquier orden.

```json
{
  "render": {
    "type": "tabs",
    "metadata": {
      "tab_position": "top",
      "lazy_load": false
    }
  }
}
```

### `modal`

El formulario se presenta dentro de un dialog/modal. El frontend es responsable de abrirlo y cerrarlo; el bundle solo expone la metadata de configuración.

```json
{
  "render": {
    "type": "modal",
    "metadata": {
      "size": "md",
      "position": "center",
      "dismissible": true,
      "show_close_button": true
    }
  }
}
```

| Parámetro en `parameters.modal` | Descripción | Valores | Default |
|---|---|---|---|
| `size` | Ancho del dialog | `"sm"` / `"md"` / `"lg"` / `"xl"` / `"full"` | `"md"` |
| `position` | Posición en pantalla | `"center"` / `"top"` / `"bottom"` / `"drawer-right"` / `"drawer-left"` | `"center"` |
| `dismissible` | Cierra al hacer click fuera | `true` / `false` | `true` |
| `show_close_button` | Muestra botón X de cierre | `true` / `false` | `true` |

---

## Renders de Section

Configuran cómo se presenta una sección y sus grupos.

### `simple` (default)

Grupos visibles directamente, uno debajo del otro.

```json
{ "render": { "type": "simple", "metadata": {} } }
```

### `accordion`

Los grupos se colapsan en acordeón. Solo uno abierto a la vez por defecto.

```json
{
  "render": {
    "type": "accordion",
    "metadata": {
      "allow_multiple": false,
      "default_open": "first"
    }
  }
}
```

| Parámetro | Descripción | Valores |
|---|---|---|
| `allow_multiple` | Permite abrir varios paneles a la vez | `true` / `false` |
| `default_open` | Cuál está abierto al cargar | `"first"` / `"none"` / tag del grupo |

### `tabs`

Los grupos se presentan como pestañas dentro de la sección.

```json
{
  "render": {
    "type": "tabs",
    "metadata": {
      "tab_position": "top",
      "lazy_load": false
    }
  }
}
```

### `collapsible`

Similar a accordion pero cada grupo puede colapsarse independientemente.

```json
{
  "render": {
    "type": "collapsible",
    "metadata": {
      "default_collapsed": false
    }
  }
}
```

---

## Renders de Group

Configuran cómo se distribuyen los campos dentro de un grupo.

### `simple` (default)

Campos en columna vertical, uno debajo del otro.

```json
{ "render": { "type": "simple", "metadata": {} } }
```

### `matrix`

Los campos se disponen en una cuadrícula. Útil para formularios de tipo tabla.

```json
{
  "render": {
    "type": "matrix",
    "metadata": {
      "columns": 3,
      "responsive_breakpoint": "md"
    }
  }
}
```

| Parámetro | Descripción |
|---|---|
| `columns` | Número de columnas de la cuadrícula |
| `responsive_breakpoint` | Breakpoint bajo el que pasa a 1 columna (`"sm"`, `"md"`, `"lg"`) |

### `tabs`

Los campos se agrupan en sub-pestañas dentro del grupo.

```json
{
  "render": {
    "type": "tabs",
    "metadata": {
      "tab_position": "left"
    }
  }
}
```

---

## Cómo se define el render en BD

El tipo de render se almacena en `parameters.type_render` de cada entidad. El resto de parámetros del render van también en `parameters`:

```json
{
  "type_render": "stepper",
  "show_progress": true,
  "navigation": "bottom",
  "allow_skip": false,
  "steps_orientation": "horizontal"
}
```

El resolver lee `parameters['type_render']`, busca el render en el registry, y llama `renderMeta($parameters)` que extrae y valida los parámetros relevantes. El resultado se serializa en la llave `render` del DTO como `{"type": "...", "metadata": {...}}`.

---

## Registrar un render propio

Ver [Extensibilidad](extending.md#renders).
