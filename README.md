# letkode/form-schema

Symfony Bundle para formularios dinámicos configurables desde base de datos.

Modela formularios en una jerarquía **Form → Section → Group → Field**, con i18n dinámico, 18 tipos de campo predefinidos y extensibles, opciones desde catálogo interno o repositorios del proyecto, renders estructurales en 3 niveles y caché PSR-6 opt-in.

**Requiere** PHP 8.4 · Symfony 7.x · Doctrine ORM ^3.4

---

## Documentación

| Documento | Contenido |
|---|---|
| [Instalación](docs/installation.md) | Composer, bundle, migraciones, comando de verificación |
| [Configuración](docs/configuration.md) | Todas las opciones del bundle con valores por defecto |
| [Resolver](docs/resolver.md) | API del `FormSchemaResolver`, fluent builder, DTOs de salida |
| [Tipos de campo](docs/field-types.md) | Los 18 tipos built-in, atributos, parámetros y opciones |
| [Fuentes de opciones](docs/options-sources.md) | `general` (catálogo BD), `entity` (repositorios del proyecto) |
| [Renders](docs/renders.md) | Renders de Form, Section y Group disponibles y su configuración |
| [Caché](docs/cache.md) | Activar caché PSR-6, invalidación automática y manual |
| [Nombres de tabla](docs/table-names.md) | Personalizar tablas con `table_prefix` y `table_names` |
| [Extensibilidad](docs/extending.md) | Crear tipos de campo, fuentes y renders propios |
| [Traducciones](docs/translations.md) | Estructura i18n, cómo se resuelve el locale activo |

## Inicio rápido

```bash
composer require letkode/form-schema
php bin/console doctrine:migrations:migrate
php bin/console letkode:form-schema:install
```

```php
// En cualquier servicio o controller
$form = $resolver
    ->schema('user_onboarding')
    ->withLocale('es')
    ->withContext('create')
    ->resolve();
```
