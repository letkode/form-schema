# Instalación

## 1. Composer

```bash
composer require letkode/form-schema
```

Symfony Flex registra el bundle automáticamente. Si no usas Flex, agrégalo a mano:

```php
// config/bundles.php
return [
    // ...
    Letkode\FormSchema\LetkodeFormSchemaBundle::class => ['all' => true],
];
```

## 2. Mapear las entidades en tu Entity Manager

El bundle no registra las entidades automáticamente. Debes mapearlas al Entity Manager que gestiona el schema donde quieres instalar las tablas.

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        entity_managers:
            default:                        # nombre de tu EM — cámbialo si es distinto
                mappings:
                    LetkodeFormSchema:
                        type: attribute
                        is_bundle: false
                        dir: '%kernel.project_dir%/vendor/letkode/form-schema/src/Domain/Entity'
                        prefix: 'Letkode\FormSchema\Domain\Entity'
```

> **Multi-schema / multi-tenant:** si tienes varios EMs (p.ej. `hub` para el schema público y `tenant` para schemas dinámicos), mapea las entidades únicamente en el EM del schema de sistema, no en el de tenant.

## 3. Generar y aplicar las migraciones

Una vez mapeadas las entidades, genera la migración con tu configuración habitual de Doctrine Migrations:

```bash
# Ejemplo con un único archivo de configuración
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# Ejemplo con configuraciones separadas por schema (multi-tenant)
php bin/console doctrine:migrations:diff --configuration=config/migrations/hub.yaml
php bin/console doctrine:migrations:migrate --configuration=config/migrations/hub.yaml
```

Esto crea las 6 tablas del bundle:

| Tabla (default) | Entidad |
|---|---|
| `form` | `Form` — formulario raíz |
| `form_section` | `FormSection` — sección dentro del formulario |
| `form_group` | `FormGroup` — grupo de campos dentro de una sección |
| `form_field` | `FormField` — campo individual |
| `form_option_general` | `FormOptionGeneral` — catálogo de opciones reutilizable |
| `form_option_general_value` | `FormOptionGeneralValue` — valores del catálogo |

> Los nombres de tabla son personalizables con `table_prefix` y `table_names`. Ver [Nombres de tabla](table-names.md).

## 4. Archivo de configuración

Crea el archivo de configuración del bundle (o déjalo con los valores por defecto):

```yaml
# config/packages/letkode_form_schema.yaml
letkode_form_schema:
    default_locale: 'es'
```

Ver la referencia completa en [Configuración](configuration.md).

## Requisitos

| Dependencia | Versión mínima |
|---|---|
| PHP | 8.4 |
| Symfony | ^7.0 |
| Doctrine ORM | ^3.4 |
| Gedmo/DoctrineExtensions | ^3.0 (para SoftDelete) |
| symfony/uid | ^7.0 (para UuidV7) |
