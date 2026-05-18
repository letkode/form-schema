# Caché

El bundle incluye un sistema de caché PSR-6 opt-in que evita consultas a BD en cada resolución de formulario. Se activa con un único flag de configuración.

## Activar la caché

```yaml
# config/packages/letkode_form_schema.yaml
letkode_form_schema:
    cache:
        enabled: true
        pool: cache.app          # cualquier PSR-6 pool
        ttl: 3600                # segundos (0 = sin expiración)
        key_prefix: 'fsb'
        auto_invalidate: true    # ver sección Invalidación
```

Cuando `enabled: true`, el contenedor decora automáticamente `FormSchemaResolverInterface` con `CachedFormSchemaResolver`. No requiere ningún cambio en el código del proyecto.

## Pool de caché

El bundle funciona con cualquier pool PSR-6 (`CacheItemPoolInterface`). Para invalidación por tags (recomendado en producción) usa un pool `TagAwareAdapterInterface`:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            cache.redis_tag_aware:
                adapter: cache.adapter.redis
                tags: true

# config/packages/letkode_form_schema.yaml
letkode_form_schema:
    cache:
        enabled: true
        pool: cache.redis_tag_aware
```

Si el pool no soporta tags, la invalidación hace `clear()` del pool completo en lugar de invalidar por tag.

## Clave de caché

La clave se construye con un hash de todos los parámetros del builder:

```
{key_prefix}.{xxh3(tag + locale + context + sorted_filters)}
```

Cada combinación única de `schema/locale/context/includingSections/excludingSections/includingGroups/excludeGroups` tiene su propia entrada de caché. El orden de los arrays de filtro no afecta la clave (se normalizan con `sort()` antes del hash).

## Invalidación

### Automática (recomendado)

Con `auto_invalidate: true` el bundle registra un `DoctrineCacheInvalidationSubscriber` que escucha los eventos `postPersist`, `postUpdate` y `postRemove` de Doctrine.

Cuando cualquier entidad del bundle cambia en BD, se invalida automáticamente la caché del formulario afectado usando su tag:

| Entidad modificada | Tag invalidado |
|---|---|
| `Form` | `{prefix}.{form.tag}` |
| `FormSection` | `{prefix}.{section.form.tag}` |
| `FormGroup` | `{prefix}.{group.section.form.tag}` |
| `FormField` | `{prefix}.{field.group.section.form.tag}` |
| `FormOptionGeneral` | `{prefix}.{option.tag}` |
| `FormOptionGeneralValue` | `{prefix}.{value.group.tag}` |

### Manual

Si necesitas invalidar desde el código del proyecto (ej. tras un import masivo):

```php
use Letkode\FormSchema\Infrastructure\Cache\FormSchemaCacheInvalidator;

class MyImportService
{
    public function __construct(
        private readonly FormSchemaCacheInvalidator $cacheInvalidator,
    ) {}

    public function afterImport(string $formTag): void
    {
        $this->cacheInvalidator->invalidate($formTag);
    }
}
```

`FormSchemaCacheInvalidator::invalidate(string $tag)` usa invalidación por tag si el pool lo soporta, o `clear()` como fallback.

## Desactivar caché en tests

```yaml
# config/packages/test/letkode_form_schema.yaml
letkode_form_schema:
    cache:
        enabled: false
```

O usando el atributo `#[When(env: 'test')]` en servicios que no quieras que se registren.

## Consideraciones

- La caché almacena `FormDTO` serializado. Si cambias la estructura del DTO entre versiones, limpia el pool tras el deploy.
- El TTL por defecto es 3600 s (1 hora). Ajusta según la frecuencia de cambios de los formularios en BD.
- Con `auto_invalidate: true` y un pool con tags, el TTL actúa solo como seguro ante fallos de invalidación.
