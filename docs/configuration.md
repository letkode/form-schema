# Configuración del bundle

Referencia completa de todas las opciones disponibles en `config/packages/letkode_form_schema.yaml`.

```yaml
letkode_form_schema:

    # Locale usado cuando el resolver no recibe uno explícito.
    # También es el fallback si la traducción pedida no existe.
    default_locale: 'es'                        # default: 'es'

    # Locales disponibles en el proyecto (informativo, no es validación).
    available_locales: ['es', 'en']             # default: ['es']

    # Namespace de entidades del proyecto para la fuente de opciones "entity".
    # Acepta múltiples namespaces separados por coma en un array.
    entity_namespace: 'App\Entity'              # default: 'App\Entity'

    # ── Nombres de tabla ────────────────────────────────────────────────────
    # Prefijo aplicado a todas las tablas del bundle.
    # La cadena vacía (default) deja los nombres por defecto.
    table_prefix: ''                            # default: ''

    # Override fino por entidad. null → usa table_prefix + nombre por defecto.
    # Las entradas aquí definen el nombre FINAL (el prefijo no se aplica encima).
    table_names:
        form: ~                                 # default: 'form'
        form_section: ~                         # default: 'form_section'
        form_group: ~                           # default: 'form_group'
        form_field: ~                           # default: 'form_field'
        form_option_general: ~                  # default: 'form_option_general'
        form_option_general_value: ~            # default: 'form_option_general_value'

    # ── Caché PSR-6 ─────────────────────────────────────────────────────────
    cache:
        # Activa el decorator CachedFormSchemaResolver.
        enabled: false                          # default: false

        # Servicio del pool de caché. Cualquier PSR-6 compatible.
        # Para invalidación por tags usa un pool TagAware (ej. cache.redis_tag_aware).
        pool: cache.app                         # default: 'cache.app'

        # TTL en segundos. 0 = sin expiración.
        ttl: 3600                               # default: 3600

        # Prefijo de las claves de caché generadas.
        key_prefix: 'fsb'                       # default: 'fsb'

        # Registra automáticamente el DoctrineCacheInvalidationSubscriber.
        # Invalida la caché cuando Form/Section/Group/Field cambian en BD.
        auto_invalidate: true                   # default: true

    # ── Componentes deshabilitados ───────────────────────────────────────────
    # Permite desactivar tipos/fuentes/renders built-in que el proyecto no necesita.
    # Usa el valor de getName() de cada implementación.

    disabled_field_types: []                    # ej. ['rating', 'date-range']
    disabled_options_sources: []                # ej. ['general']
    disabled_form_renders: []                   # ej. ['wizard']
    disabled_section_renders: []               # ej. ['accordion', 'collapsible']
    disabled_group_renders: []                  # ej. ['matrix']
```

## Ejemplos de configuración habitual

### Proyecto simple (sin caché, sin personalización)

```yaml
letkode_form_schema:
    default_locale: 'es'
```

### Proyecto con caché Redis y prefijo de tablas

```yaml
letkode_form_schema:
    default_locale: 'es'
    available_locales: ['es', 'en', 'pt']
    table_prefix: 'app_'
    cache:
        enabled: true
        pool: cache.redis_tag_aware
        ttl: 7200
        key_prefix: 'forms'
        auto_invalidate: true
```

### Monolito modular (múltiples namespaces de entidades)

```yaml
letkode_form_schema:
    entity_namespace: 'App\Entity'
    # Los repositorios habilitados como fuente de opciones se declaran
    # con #[AsFormOptionsProvider] en la clase — no dependen del namespace.
    # Ver docs/options-sources.md
```

### Tablas con nombre personalizado por entidad

```yaml
letkode_form_schema:
    table_prefix: 'dyn_'
    table_names:
        form: 'core_forms'          # nombre final: core_forms (sin prefijo)
        form_option_general: 'option_catalog'
    # Resultado: core_forms, dyn_form_section, dyn_form_group,
    #            dyn_form_field, option_catalog, dyn_form_option_general_value
```
