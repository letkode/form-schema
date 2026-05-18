# Nombres de tabla

Por defecto el bundle usa estos nombres de tabla:

| Entidad | Tabla por defecto |
|---|---|
| `Form` | `form` |
| `FormSection` | `form_section` |
| `FormGroup` | `form_group` |
| `FormField` | `form_field` |
| `FormOptionGeneral` | `form_option_general` |
| `FormOptionGeneralValue` | `form_option_general_value` |

Si el proyecto ya usa alguno de esos nombres, o si sigue una convención de prefijos, puedes personalizarlos sin tocar el código del bundle.

## `table_prefix` — prefijo global

Aplica un prefijo a **todas** las tablas del bundle:

```yaml
letkode_form_schema:
    table_prefix: 'app_'
```

Resultado:

```
app_form
app_form_section
app_form_group
app_form_field
app_form_option_general
app_form_option_general_value
```

## `table_names` — override por entidad

Sobrescribe el nombre de tablas individuales. El valor que escribes es el **nombre final** — el prefijo no se aplica encima de estas entradas.

```yaml
letkode_form_schema:
    table_names:
        form: 'my_forms'
        form_field: 'my_fields'
```

Resultado (sin prefijo):

```
my_forms                       ← nombre final explícito
form_section                   ← nombre por defecto
form_group                     ← nombre por defecto
my_fields                      ← nombre final explícito
form_option_general            ← nombre por defecto
form_option_general_value      ← nombre por defecto
```

## Combinación de ambos

`table_names` tiene prioridad sobre `table_prefix`. Las entidades con nombre explícito usan ese nombre; el resto recibe el prefijo.

```yaml
letkode_form_schema:
    table_prefix: 'dyn_'
    table_names:
        form: 'core_forms'
        form_option_general: 'option_catalog'
```

Resultado:

```
core_forms                     ← nombre explícito (sin prefijo)
dyn_form_section               ← prefijo + defecto
dyn_form_group                 ← prefijo + defecto
dyn_form_field                 ← prefijo + defecto
option_catalog                 ← nombre explícito (sin prefijo)
dyn_form_option_general_value  ← prefijo + defecto
```

## Regla de resolución

```
nombre_final = table_names[clave] ?? (table_prefix + nombre_por_defecto)
```

## Importante: migraciones

Cuando cambias nombres de tabla **antes de ejecutar la migración inicial**, el cambio se aplica directamente porque la migración usa la metadata de Doctrine (que ya tiene el nombre resuelto).

Si cambias nombres de tabla **después de haber ejecutado migraciones**, necesitas crear una nueva migración de renombrado:

```bash
php bin/console doctrine:migrations:diff
# Revisa el diff generado antes de aplicar
php bin/console doctrine:migrations:migrate
```

## Implementación interna

El `TableNameSubscriber` escucha el evento `loadClassMetadata` de Doctrine e intercepta el mapeo de las 6 entidades del bundle antes de que Doctrine lo finalice. Esto permite cambiar el nombre de tabla en runtime sin modificar los atributos PHP de las entidades.

El subscriber se registra siempre (incluso con config por defecto), pero cuando `table_prefix` está vacío y `table_names` son todos `null`, los nombres resueltos coinciden exactamente con los del `#[ORM\Table]` de cada entidad, por lo que no hay impacto.
