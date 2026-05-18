# Changelog

## [1.0.0] - 2026-05-16

### Added
- Initial release of `letkode/form-schema` Symfony Bundle
- 6 Doctrine entities: `Form`, `FormSection`, `FormGroup`, `FormField`, `FormOptionGeneral`, `FormOptionGeneralValue`
- 18 built-in field types with extensible Strategy + Registry pattern
- 2 built-in option sources: `general` (internal catalog) and `entity` (project repositories via `#[AsFormOptionsProvider]`)
- 3-level structural renders: `FormRender` (simple/stepper/wizard/tabs), `SectionRender` (simple/accordion/tabs/collapsible), `GroupRender` (simple/matrix/tabs)
- `FormSchemaResolver` with immutable builder pattern (`schema()->withLocale()->withContext()->resolve()`)
- PSR-6 opt-in cache with `TagAwareAdapterInterface` support and `FormSchemaCacheInvalidator`
- `DoctrineCacheInvalidationSubscriber` opt-in for automatic cache invalidation
- PHP 8.4 native features: property hooks, asymmetric visibility, `array_find`/`array_any`/`array_all`
- Soft delete on all entities via Gedmo SoftDeleteable
- Doctrine migrations bundled in `src/Migrations/`
- `letkode:form-schema:install` console command
