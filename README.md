# letkode/form-schema

Symfony Bundle para formularios dinámicos configurables desde base de datos.

Modela formularios en una jerarquía **Form → Section → Group → Field**, con i18n dinámico, 22 tipos de campo predefinidos y extensibles, opciones desde catálogo interno o repositorios del proyecto, renders estructurales en 3 niveles y caché PSR-6 opt-in.

**Requiere** PHP 8.4 · Symfony 7.x · Doctrine ORM ^3.4

---

## Documentación

| Documento | Contenido |
|---|---|
| [Instalación](docs/installation.md) | Composer, bundle, mapeo de entidades, migraciones |
| [Configuración](docs/configuration.md) | Todas las opciones del bundle con valores por defecto |
| [Resolver](docs/resolver.md) | API del `FormSchemaResolver`, fluent builder, DTOs de salida |
| [Tipos de campo](docs/field-types.md) | Los 22 tipos built-in, atributos, parámetros UI y `option.data` |
| [Fuentes de opciones](docs/options-sources.md) | `general` (catálogo BD), `entity` (repositorios del proyecto) |
| [Renders](docs/renders.md) | Renders de Form, Section y Group disponibles y su configuración |
| [Caché](docs/cache.md) | Activar caché PSR-6, invalidación automática y manual |
| [Nombres de tabla](docs/table-names.md) | Personalizar tablas con `table_prefix` y `table_names` |
| [Extensibilidad](docs/extending.md) | Crear tipos de campo, fuentes y renders propios |
| [Traducciones](docs/translations.md) | Estructura i18n, cómo se resuelve el locale activo |

---

## Inicio rápido

```bash
composer require letkode/form-schema
```

Mapea las entidades en tu Entity Manager (ver [Instalación](docs/installation.md)):

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        entity_managers:
            default:
                mappings:
                    LetkodeFormSchema:
                        type: attribute
                        is_bundle: false
                        dir: '%kernel.project_dir%/vendor/letkode/form-schema/src/Domain/Entity'
                        prefix: 'Letkode\FormSchema\Domain\Entity'
```

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Resuelve el schema de un formulario:

```php
$form = $resolver
    ->schema('user_onboarding')
    ->withLocale('es')
    ->withContext('create')   // filtra y sobreescribe atributos según actions[create]
    ->resolve();
```

---

## Estructura del paquete

### Jerarquía de entidades

```
Form
└── FormSection[]
    └── FormGroup[]
        └── FormField[]
```

Cada nivel puede tener `parameters` (JSON libre), traducciones y un `type_render` que controla su render estructural.

### Renders en 3 niveles

| Nivel | Responsabilidad | Tipos disponibles |
|---|---|---|
| `FormRender` | Navegación entre secciones | `default`, `stepper`, `tabs` |
| `SectionRender` | Distribución de grupos dentro de una sección | `default`, `accordion`, `collapsible`, `tabs` |
| `GroupRender` | Distribución de campos dentro de un grupo | `default`, `fieldset`, `matrix`, `tabs` |

### Contexto de acción (`actions`)

Los campos pueden configurar su visibilidad y atributos por contexto:

```json
{
  "attributes": {
    "required": false,
    "actions": {
      "create": { "enabled": true, "required": true },
      "edit":   { "enabled": true, "required": false },
      "show":   { "enabled": true, "readonly": true }
    }
  }
}
```

El resolver aplica `withContext('create')` para filtrar campos deshabilitados y sobrescribir `required`, `readonly` y otros atributos dinámicos.

### Tipos de campo built-in

| Categoría | Tipos |
|---|---|
| Texto | `text`, `email`, `password`, `textarea`, `rich_text` |
| Numérico | `number`, `range` |
| Fecha | `date`, `datetime`, `time` |
| Selección | `select`, `select-multiple`, `radio`, `checkbox`, `combobox`, `duallist`, `tree` |
| Especiales | `file`, `switch`, `rating`, `pin` |
