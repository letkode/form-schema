# Changelog

## [1.0.0] - 2026-06-08

### Added
- Initial release of `letkode/form-schema` Symfony Bundle
- 6 Doctrine entities: `Form`, `FormSection`, `FormGroup`, `FormField`, `FormOptionGeneral`, `FormOptionGeneralValue`
- 22 built-in field types with extensible Strategy + Registry pattern
- `FieldTypeInterface::getDefaultParams(): array` — each FieldType declares its UI params with defaults; the resolver merges them with DB-stored params (DB wins)
- `SelectFieldType` (`select`) — single-select. Params: `size`, `searchable`, `tags_mode`, `search_limit`, `min_search_length`. Value: `string|null`
- `SelectMultipleFieldType` (`select-multiple`) — multi-select. Params: same as `select` plus `max_count_items`. Value: always `array`; `formatDefaultValue` normalises `null → []` and `string → [string]`
- `RangeFieldType` — range slider with `color`, `min`, `max`, `step`, `show_steps` params (FlyonUI `range`)
- `ComboboxFieldType` — autocomplete with `open_on_focus`, `min_search_length`, `api_url` params (FlyonUI `combo-box`)
- `PinFieldType` — PIN/OTP input with `variant`, `length`, `input_type`, `chars_pattern` params (FlyonUI `pin-input`)
- `TreeFieldType` — hierarchical tree with `selection_mode` (checkbox/radio), `auto_select_children`, `expanded_by_default` params (FlyonUI `tree-view`); tree hierarchy via `option.data.children`
- `DuallistFieldType` — dual-panel selector with `searchable`, `source_label`, `target_label`, `show_move_all_buttons` params
- `OptionDTO.data` (default `[]`) — universal extensibility field for options: data-attrs for client-side interaction filtering and `data.children` for tree hierarchy
- `OptionDTO.label` / `OptionGroupDTO.label` — display-text key for options and option groups
- 2 built-in option sources: `general` (internal catalog) and `entity` (project repositories via `#[AsFormOptionsProvider]`)
- 3-level structural renders with clear separation of responsibilities:
  - `FormRender` — defines how sections are navigated: `default`, `stepper`, `tabs`
  - `SectionRender` — defines how groups are distributed within a section: `default`, `accordion`, `collapsible`, `tabs`
  - `GroupRender` — defines how fields are distributed within a group: `default`, `fieldset`, `matrix`, `tabs`
- `FieldAttributes.actions` — context-keyed map replacing individual boolean flags. Each context entry supports `enabled` (visibility) plus per-context attribute overrides (`required`, `readonly`, etc.)
- `FieldAttributes::withActionOverrides(string $context)` — returns a new instance with overrides applied for the given context
- `FormSchemaResolver` with immutable builder pattern (`schema()->withLocale()->withContext()->resolve()`)
- PSR-6 opt-in cache with `TagAwareAdapterInterface` support and `FormSchemaCacheInvalidator`
- `DoctrineCacheInvalidationSubscriber` opt-in for automatic cache invalidation
- Interaction handler system with 7 built-in handlers (`toggle_visibility`, `toggle_required`, `set_value`, `filter_options`, `ajax_validate`, `set_date_constraint`, `compute`)
- PHP 8.4 native features: property hooks, asymmetric visibility, `array_find`/`array_any`/`array_all`
- Soft delete on all entities via Gedmo SoftDeleteable
- Entity mapping via project's Doctrine Entity Manager (`type: attribute`, no auto-registration)
