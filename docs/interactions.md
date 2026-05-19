# Interacciones de fields (on_action)

Las interacciones permiten declarar **qué debe ocurrir cuando el usuario interactúa con un field**. El bundle almacena la configuración y la expone en el DTO; el UI es responsable de ejecutar la lógica.

Cada field puede tener una lista de interacciones en `field.interactions`. Cuando `interactions` está vacío o no definido, el field no tiene comportamiento dinámico.

---

## Estructura de una interacción

```json
{
  "trigger":   "change",
  "action":    "toggle_visibility",
  "target":    ["billing_address", "billing_city"],
  "condition": { "operator": "equals", "value": true },
  "params":    {}
}
```

| Campo | Tipo | Descripción |
|---|---|---|
| `trigger` | string | Evento que activa la interacción |
| `action` | string | Qué hacer cuando se activa |
| `target` | string \| string[] \| null | Tag(s) del field afectado. `null` = el propio field |
| `condition` | object | Condición opcional sobre el valor actual. `{}` = ejecutar siempre |
| `params` | object | Config específica de cada `action` |

---

## Triggers

| Valor | Cuándo se dispara |
|---|---|
| `change` | El valor cambia (commit, no cada keystroke) |
| `input` | Cada keystroke — usar `debounce` en `params` |
| `blur` | El field pierde el foco |
| `focus` | El field gana el foco |

---

## Conditions

Si `condition` es `{}`, la acción se ejecuta siempre al dispararse el trigger.

| `operator` | Descripción |
|---|---|
| `equals` | El valor actual es igual a `condition.value` |
| `not_equals` | El valor actual es distinto de `condition.value` |
| `in` | El valor actual está en el array `condition.value` |
| `not_in` | El valor actual no está en el array `condition.value` |
| `truthy` | El valor actual es truthy (`condition.value` ignorado) |
| `falsy` | El valor actual es falsy (`condition.value` ignorado) |

---

## Acciones disponibles

### `filter_options`

Filtra las opciones de un field de tipo lista cuando cambia el field actual. Soporta dos modos.

**Aplicable como trigger en:** `list`, `radio`, `multilist`, `list-group`

```json
// Modo server: el UI re-solicita las opciones al backend con el valor actual como parámetro
{
  "trigger": "change",
  "action": "filter_options",
  "target": "city",
  "condition": {},
  "params": {
    "mode": "server",
    "filter_param": "country_id"
  }
}
```

```json
// Modo client: el UI filtra las opciones ya cargadas buscando en option.extra[filter_key]
{
  "trigger": "change",
  "action": "filter_options",
  "target": "city",
  "condition": {},
  "params": {
    "mode": "client",
    "filter_key": "country_id"
  }
}
```

| Param | Requerido | Descripción |
|---|---|---|
| `mode` | Sí | `"server"` o `"client"` |
| `filter_param` | Si `mode=server` | Clave con la que se pasa el valor actual en los params del resolver de opciones del target |
| `filter_key` | Si `mode=client` | Clave en `option.extra` donde se busca el valor para comparar |

---

### `toggle_visibility`

Muestra u oculta uno o varios fields según el valor actual.

```json
{
  "trigger": "change",
  "action": "toggle_visibility",
  "target": ["billing_address", "billing_city"],
  "condition": { "operator": "equals", "value": true },
  "params": {}
}
```

Si `condition` está vacío, la visibilidad se controla con el valor truthy/falsy del field.

---

### `toggle_required`

Hace requerido u opcional otro field de forma condicional.

```json
{
  "trigger": "change",
  "action": "toggle_required",
  "target": "company_name",
  "condition": { "operator": "equals", "value": true },
  "params": {}
}
```

---

### `set_value`

Limpia o copia el valor de otro field.

```json
// Limpiar ciudad cuando cambia país
{
  "trigger": "change",
  "action": "set_value",
  "target": "city",
  "condition": {},
  "params": { "value": null }
}
```

```json
// Copiar el valor de otro field (patrón "igual que facturación")
{
  "trigger": "change",
  "action": "set_value",
  "target": "shipping_address",
  "condition": { "operator": "equals", "value": true },
  "params": { "copy_from": "billing_address" }
}
```

| Param | Descripción |
|---|---|
| `value` | Valor fijo a asignar al target (puede ser `null` para limpiar) |
| `copy_from` | Tag del field cuyo valor se copia al target (exclusivo con `value`) |

---

### `ajax_validate`

Envía el valor actual a un endpoint HTTP del proyecto consumidor para validarlo.

**Aplicable en:** `string`, `email`, `phone`, `number`

```json
{
  "trigger": "blur",
  "action": "ajax_validate",
  "target": null,
  "condition": {},
  "params": {
    "endpoint": "/api/check-username",
    "method": "GET",
    "debounce": 300
  }
}
```

| Param | Requerido | Default | Descripción |
|---|---|---|---|
| `endpoint` | Sí | — | URL del endpoint del proyecto |
| `method` | No | `"GET"` | Método HTTP |
| `debounce` | No | `0` | Ms de debounce (útil con trigger `input`) |

El bundle solo almacena la configuración; el endpoint lo define e implementa el proyecto consumidor.

---

### `set_date_constraint`

Ajusta el valor mínimo o máximo de un field de fecha usando el valor actual como referencia.

**Aplicable como target en:** `date`, `datetime`, `date-range`

```json
{
  "trigger": "change",
  "action": "set_date_constraint",
  "target": "end_date",
  "condition": {},
  "params": { "constraint": "min" }
}
```

| Param | Valores | Descripción |
|---|---|---|
| `constraint` | `"min"` / `"max"` | Si el valor actual pasa a ser el mínimo o el máximo del target |

---

### `compute`

Calcula el valor de un field a partir de una expresión que combina valores de otros fields.

**Aplicable en:** `number`, `string`, cualquier field numérico o de texto

```json
// El propio field calcula su valor (target: null)
{
  "trigger": "change",
  "action": "compute",
  "target": null,
  "condition": {},
  "params": {
    "expression": "{price} * {quantity}",
    "sources": ["price", "quantity"],
    "decimals": 2
  }
}
```

```json
// Actualiza un field distinto
{
  "trigger": "change",
  "action": "compute",
  "target": "total_with_tax",
  "condition": {},
  "params": {
    "expression": "{subtotal} + ({subtotal} * {tax_rate} / 100)",
    "sources": ["subtotal", "tax_rate"],
    "decimals": 2
  }
}
```

| Param | Requerido | Descripción |
|---|---|---|
| `expression` | Sí | Expresión matemática usando `{field_tag}` como variables |
| `sources` | Sí | Tags de los fields que se observan para re-calcular |
| `decimals` | No | Número de decimales para redondear el resultado |

El UI evalúa la expresión sustituyendo `{tag}` por el valor actual de cada field. El bundle no parsea ni ejecuta la expresión.

---

## Cómo definir interacciones en BD

Las interacciones se almacenan en la columna `form_field.interactions` (JSON nullable). Es un array de objetos, cada uno con la estructura descrita arriba.

Un field sin interacciones tiene `interactions = null` (o array vacío). El DTO siempre expone `interactions` como array (vacío si no hay ninguna).

---

## Múltiples interacciones en un mismo field

Un field puede tener varias interacciones, incluso con el mismo trigger:

```json
[
  {
    "trigger": "change",
    "action": "set_value",
    "target": "city",
    "condition": {},
    "params": { "value": null }
  },
  {
    "trigger": "change",
    "action": "filter_options",
    "target": "city",
    "condition": {},
    "params": { "mode": "server", "filter_param": "country_id" }
  }
]
```

El UI ejecuta las interacciones en el orden en que aparecen en el array.

---

## Registrar un handler propio

El sistema de interacciones usa el mismo patrón **Strategy + Registry** que los field types y las fuentes de opciones. Cualquier proyecto puede registrar nuevas acciones creando una clase con `#[AsInteractionHandler]`.

### 1. Crear el handler

```php
use Letkode\FormSchema\Attribute\AsInteractionHandler;
use Letkode\FormSchema\Infrastructure\InteractionHandler\AbstractInteractionHandler;

#[AsInteractionHandler]
final class SendWebhookInteractionHandler extends AbstractInteractionHandler
{
    public static function getName(): string
    {
        return 'send_webhook';
    }

    public function getDefaultParams(): array
    {
        return [
            'method' => 'POST',
            'retry'  => 0,
        ];
    }
}
```

### 2. Registrar el servicio (si no usas autowire)

Si el proyecto tiene autowire activado (habitual en Symfony), el servicio se registra solo. Si no:

```yaml
# config/services.yaml
App\Interaction\SendWebhookInteractionHandler:
    tags:
        - { name: form_schema.interaction_handler }
```

### 3. Usar en BD

```json
{
  "trigger": "change",
  "action": "send_webhook",
  "target": null,
  "condition": { "operator": "truthy" },
  "params": { "url": "https://hooks.example.com/form-submitted" }
}
```

El DTO que recibe el UI tendrá los params mergeados con los defaults del handler:
```json
{
  "trigger": "change",
  "action": "send_webhook",
  "params": { "url": "https://hooks.example.com/form-submitted", "method": "POST", "retry": 0 }
}
```

> Si se almacena una `action` cuyo handler no está registrado, el resolver lanza `UnknownInteractionHandlerException` en tiempo de resolve. Esto garantiza que todos los tipos de acción estén siempre bien definidos.
