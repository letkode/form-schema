# Traducciones

El bundle implementa un sistema de i18n dinámico almacenado en BD como JSON. Las traducciones son independientes de los archivos `.yaml`/`.xliff` de Symfony — no requieren `translation:update` ni `cache:clear` para añadir un idioma nuevo.

## Estructura del JSON

Todas las entidades (`Form`, `FormSection`, `FormGroup`, `FormField`, `FormOptionGeneral`, `FormOptionGeneralValue`) almacenan sus traducciones en una columna JSON con la siguiente estructura:

```json
{
  "es": {
    "name": "Nombre en español",
    "description": "Descripción en español",
    "placeholder": "Escribe aquí…"
  },
  "en": {
    "name": "Name in English",
    "description": "Description in English",
    "placeholder": "Type here…"
  },
  "pt": {
    "name": "Nome em português"
  }
}
```

El primer nivel es el **locale** (ej. `"es"`, `"en"`, `"pt"`). El segundo nivel son los **campos traducibles** de la entidad.

> Este orden — `{locale: {campo: valor}}` — es el canónico en todo el bundle. No confundir con el orden inverso `{campo: {locale: valor}}`.

## Campos traducibles por entidad

| Entidad | Campos traducibles |
|---|---|
| `Form` | `name` |
| `FormSection` | `name`, `description` |
| `FormGroup` | `name`, `description` |
| `FormField` | `name`, `description`, `placeholder` |
| `FormOptionGeneral` | `name` |
| `FormOptionGeneralValue` | `name` |

## Resolución del locale activo

El resolver sigue este orden de prioridad para determinar el locale:

```
1. locale explícito vía withLocale('en')
2. form.defaultLang (locale por defecto del formulario específico)
3. letkode_form_schema.default_locale (config global del bundle)
```

El locale activo se propaga en cascada desde `Form` hacia `Section → Group → Field` cuando se llama `$form->withActiveLocale($locale)` dentro del resolver.

## Fallback de traducción

Si un campo no tiene traducción para el locale pedido, el resolver hace fallback al locale por defecto del formulario (`defaultLang`) y luego al valor almacenado en la columna `rawName`/`rawDescription` de la entidad (el valor canónico original en BD).

```
getTranslation($locale, 'name')
  ?? getTranslation($form->defaultLang, 'name')
  ?? $rawName
```

## `translations` en los DTOs

Los DTOs de salida incluyen el campo `translations: array|null` con el JSON completo de traducciones. Esto permite que el frontend tenga acceso a todos los idiomas disponibles sin necesitar una segunda request para cambiar de locale.

```json
{
  "id": "...",
  "name": "Nombre en español",
  "tag": "user_profile",
  "locale": "es",
  "translations": {
    "es": { "name": "Nombre en español" },
    "en": { "name": "Name in English" },
    "pt": { "name": "Nome em português" }
  },
  "sections": [...]
}
```

El campo `name` (y `description`, `placeholder`) del DTO ya viene resuelto para el locale activo. El objeto `translations` completo está disponible para que el frontend haga switching de locale en el cliente sin re-request.

## API del trait `HasTranslationsTrait`

Disponible en todas las entidades:

```php
// Leer una traducción
$value = $entity->getTranslation('en', 'name');    // string|null

// Escribir una traducción
$entity->setTranslation('en', 'name', 'My Form');

// Eliminar una traducción
$entity->setTranslation('en', 'name', null);

// Acceso directo al array completo
$all = $entity->translations;   // array|null
```

## Gestión programática

```php
$form = new Form();
$form->name = 'Formulario de registro';        // rawName (valor canónico)

// Agregar traducciones
$form->setTranslation('es', 'name', 'Formulario de registro');
$form->setTranslation('en', 'name', 'Registration form');
$form->setTranslation('pt', 'name', 'Formulário de cadastro');

$entityManager->persist($form);
$entityManager->flush();
```

## Consideraciones

- `translations: null` en BD significa que la entidad no tiene traducciones registradas. El resolver usa el `rawName` como valor.
- No hay validación de locale en escritura — puedes guardar cualquier clave. La validación de locales disponibles es responsabilidad de la aplicación.
- Las traducciones de `FormOptionGeneralValue` se aplican cuando se usa la fuente `general` con un locale activo.
