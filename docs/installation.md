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

## 2. Migraciones

El bundle incluye sus propias migraciones. Registra el namespace en tu configuración de Doctrine Migrations:

```yaml
# config/packages/doctrine_migrations.yaml
doctrine_migrations:
    migrations_paths:
        'Letkode\FormSchema\Migrations': '%kernel.project_dir%/vendor/letkode/form-schema/src/Migrations'
        # Tus propias migraciones siguen igual:
        'App\Migrations': '%kernel.project_dir%/migrations'
```

Ejecuta la migración inicial:

```bash
php bin/console doctrine:migrations:migrate
```

Esto crea las 6 tablas del bundle:

| Tabla | Entidad |
|---|---|
| `form` | `Form` — formulario raíz |
| `form_section` | `FormSection` — sección dentro del formulario |
| `form_group` | `FormGroup` — grupo de campos dentro de una sección |
| `form_field` | `FormField` — campo individual |
| `form_option_general` | `FormOptionGeneral` — catálogo de opciones reutilizable |
| `form_option_general_value` | `FormOptionGeneralValue` — valores del catálogo |

> Los nombres de tabla son personalizables. Ver [Nombres de tabla](table-names.md).

## 3. Archivo de configuración

Crea el archivo de configuración del bundle (o déjalo con los valores por defecto):

```bash
# Si usas Flex ya se genera automáticamente. Si no:
touch config/packages/letkode_form_schema.yaml
```

```yaml
letkode_form_schema:
    default_locale: 'es'
```

Ver la referencia completa en [Configuración](configuration.md).

## 4. Verificación

El bundle incluye un comando para verificar que la instalación es correcta:

```bash
php bin/console letkode:form-schema:install
```

Comprueba que las tablas existen, que los registries tienen servicios y muestra un resumen del estado.

## Requisitos

| Dependencia | Versión mínima |
|---|---|
| PHP | 8.4 |
| Symfony | ^7.0 |
| Doctrine ORM | ^3.4 |
| Gedmo/DoctrineExtensions | ^3.0 (para SoftDelete) |
| symfony/uid | ^7.0 (para UuidV7) |
