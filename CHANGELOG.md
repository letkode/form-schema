# Changelog

## [1.0.0] - 2026-05-19

### Added
- Initial release of `letkode/form-schema` Symfony Bundle
- 6 Doctrine entities: `Form`, `FormSection`, `FormGroup`, `FormField`, `FormOptionGeneral`, `FormOptionGeneralValue`
- 21 built-in field types with extensible Strategy + Registry pattern
- `FieldTypeInterface::getDefaultParams(): array` — each FieldType declares its UI params with defaults; the resolver merges them with DB-stored params (DB wins)
- `SelectFieldType` — unified single/multi select. Supports `multiple`, `searchable`, `tags_mode`, `search_limit`, `min_search_length`, `max_count_items` params (FlyonUI `advanced-select`)
- `RangeFieldType` — range slider with `color`, `min`, `max`, `step`, `show_steps` params (FlyonUI `range`)
- `ComboboxFieldType` — autocomplete with `open_on_focus`, `min_search_length`, `api_url` params (FlyonUI `combo-box`)
- `PinFieldType` — PIN/OTP input with `variant`, `length`, `input_type`, `chars_pattern` params (FlyonUI `pin-input`)
- `TreeFieldType` — hierarchical tree with `selection_mode` (checkbox/radio), `auto_select_children`, `expanded_by_default` params (FlyonUI `tree-view`); tree hierarchy via `option.data.children`
- `DuallistFieldType` — dual-panel selector with `searchable`, `source_label`, `target_label`, `show_move_all_buttons` params
- `OptionDTO.data` (default `[]`) — universal extensibility field for options: data-attrs for client-side interaction filtering and `data.children` for tree hierarchy
- 2 built-in option sources: `general` (internal catalog) and `entity` (project repositories via `#[AsFormOptionsProvider]`)
- 3-level structural renders: `FormRender` (simple/stepper/wizard/tabs/modal), `SectionRender` (simple/accordion/tabs/collapsible), `GroupRender` (simple/matrix/tabs)
- `FormSchemaResolver` with immutable builder pattern (`schema()->withLocale()->withContext()->resolve()`)
- PSR-6 opt-in cache with `TagAwareAdapterInterface` support and `FormSchemaCacheInvalidator`
- `DoctrineCacheInvalidationSubscriber` opt-in for automatic cache invalidation
- Interaction handler system with 7 built-in handlers (`toggle_visibility`, `toggle_required`, `set_value`, `filter_options`, `ajax_validate`, `set_date_constraint`, `compute`)
- PHP 8.4 native features: property hooks, asymmetric visibility, `array_find`/`array_any`/`array_all`
- Soft delete on all entities via Gedmo SoftDeleteable
- Doctrine migrations bundled in `src/Migrations/`
- `letkode:form-schema:install` console command
