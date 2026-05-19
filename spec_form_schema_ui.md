# Spec: React Form Renderer para `form-schema`

> **Estilo**: Todo el sistema visual está gestionado por **FlyonUI** y sus componentes. No se deben usar clases de Tailwind arbitrarias para construir componentes UI — siempre usar el componente FlyonUI correspondiente.

---

## 1. Contexto

La librería PHP `form-schema` genera un JSON estructurado (Form → Sections → Groups → Fields) que describe formularios dinámicos almacenados en base de datos. El objetivo es construir un renderer React que consuma ese JSON y renderice el formulario completo con soporte para:

- Múltiples layouts de formulario (simple, stepper, tabs, wizard)
- 21 tipos de campo, cada uno como componente independiente
- Precarga de valores existentes
- Validación dinámica generada desde el schema
- Interacciones reactivas entre campos (show/hide, filter options, compute, etc.)
- Subida eager de archivos
- Soporte multilenguaje

---

## 2. JSON Shape (output del backend)

```
FormDTO
  ├── id: string
  ├── name: string
  ├── tag: string
  ├── locale: string
  ├── default_locale: string
  ├── enabled: boolean
  ├── parameters: FormParameters
  ├── render: { type: 'simple'|'stepper'|'tabs'|'wizard', metadata: Record<string,unknown> }
  ├── translations: { [locale]: { name, description } } | null
  └── sections: SectionDTO[]
        ├── id, name, tag, description, position, enabled
        ├── parameters: SectionParameters
        ├── render: { type: 'simple'|'accordion'|'collapsible'|'tabs', metadata: {} }
        ├── translations: { [locale]: { name, description } } | null
        └── groups: GroupDTO[]
              ├── id, name, tag, description, position, enabled
              ├── parameters: GroupParameters
              ├── render: { type: 'simple'|'matrix'|'tabs', metadata: {} }
              ├── translations: { [locale]: { name, description } } | null
              └── fields: FieldDTO[]
                    ├── id, name, tag, type, description, position, enabled
                    ├── placeholder: string | null
                    ├── default_value: unknown
                    ├── style: Record<string, unknown>
                    ├── attributes: FieldAttributes
                    │     ├── required: boolean
                    │     ├── readonly: boolean
                    │     ├── show: boolean
                    │     ├── create: boolean
                    │     └── edit: boolean
                    ├── parameters: FieldParameters
                    ├── interactions: FieldInteraction[]   ← sistema de interacciones
                    ├── translations: { [locale]: { name, description, placeholder } } | null
                    └── options: (OptionDTO | OptionGroupDTO)[]
                          OptionDTO:      { value, text, tag, icon, color, position, data }
                          OptionGroupDTO: { text, options: OptionDTO[] }
```

---

## 3. Tipos de Campo (21)

| Categoría  | Tipos de campo |
|------------|----------------|
| Texto      | `string`, `email`, `phone`, `password`, `textarea` |
| Número     | `number`, `range` |
| Selección  | `select`, `radio`, `checkbox`, `combobox`, `tree`, `duallist` |
| Toggle     | `switch`, `rating` |
| PIN        | `pin` |
| Fecha      | `date`, `datetime`, `date-range` |
| Archivo    | `file` |
| Oculto     | `hidden` |

> **Migración**: `list` → `select`, `list-group` → `select` (grouping via `option.data.group`), `multilist` → `select` con `params.multiple: true`.

---

## 4. Render Types

| Nivel   | Tipos disponibles | Descripción |
|---------|------------------|-------------|
| Form    | `simple`   | Layout lineal, todo visible |
| Form    | `stepper`  | Pasos con progreso, navegación libre o strict según metadata |
| Form    | `tabs`     | Secciones como pestañas, posición configurable |
| Form    | `wizard`   | Pasos forzados, no se puede saltar — siempre strict |
| Section | `simple`   | Bloque lineal sin decoración |
| Section | `accordion`| Secciones colapsables, múltiples pueden estar abiertas |
| Section | `collapsible` | Una sola sección colapsable |
| Section | `tabs`     | Secciones como pestañas con lazy loading opcional |
| Group   | `simple`   | Stack lineal de campos |
| Group   | `matrix`   | Grid con rows/cols definidos en parameters |
| Group   | `tabs`     | Campos agrupados en pestañas |

---

## 5. Arquitectura de Componentes

```
<FormRenderer>                         ← entry point, provee RHF + store Zustand
  │
  ├── SimpleFormRender                 ← form.render.type === 'simple'
  ├── StepperFormRender                ← form.render.type === 'stepper'
  ├── TabsFormRender                   ← form.render.type === 'tabs'
  └── WizardFormRender                 ← form.render.type === 'wizard'
        │
        └── <FormSection>              ← itera sections[]
              ├── SimpleSectionRender
              ├── AccordionSectionRender
              ├── CollapsibleSectionRender
              └── TabsSectionRender
                    │
                    └── <FormGroup>    ← itera groups[]
                          ├── SimpleGroupRender
                          ├── MatrixGroupRender
                          └── TabsGroupRender
                                │
                                └── <FieldRenderer>   ← dispatcher por field.type
                                      ├── StringField
                                      ├── EmailField
                                      ├── PhoneField
                                      ├── NumberField
                                      ├── PasswordField
                                      ├── TextareaField
                                      ├── DateField
                                      ├── DateTimeField
                                      ├── DateRangeField
                                      ├── SelectField
                                      ├── RadioField
                                      ├── CheckboxField
                                      ├── SwitchField
                                      ├── RatingField
                                      ├── RangeField
                                      ├── ComboboxField
                                      ├── PinField
                                      ├── TreeField
                                      ├── DuallistField
                                      ├── FileField
                                      └── HiddenField
```

---

## 6. Props de `FormRenderer`

```tsx
interface FormRendererProps {
  schema: FormDTO

  // Locale para resolución de translations del JSON
  locale?: string                                       // ej: 'es', 'en'

  // Valores a precargar en el formulario
  preloadedValues?: Record<string, unknown>             // { [field.tag]: value }

  // Callback de submit — recibe objeto plano con todos los valores
  onSubmit: (values: Record<string, unknown>) => Promise<void>

  // Callback para subida eager de archivos — debe retornar la URL final
  onFileUpload: (file: File, fieldTag: string) => Promise<string>

  // Callback para cargar opciones desde servidor (usado por filter_options mode=server)
  onLoadOptions?: (
    fieldTag: string,
    params: Record<string, unknown>
  ) => Promise<OptionDTO[]>

  // Handlers custom para acciones de interacción no built-in
  interactionHandlers?: Record<string, InteractionHandler>

  // Errores del servidor por campo, para mostrar feedback de API
  serverErrors?: Record<string, string>                 // { [field.tag]: mensaje }

  className?: string
}
```

---

## 7. Stack de Validación: React Hook Form + Zod

### Por qué esta combinación

| Librería | Responsabilidad |
|----------|----------------|
| **React Hook Form** | Registra cada input, gestiona `onChange`/`onBlur`/`touched`/`errors` sin re-renders globales |
| **Zod** | Valida el schema generado dinámicamente desde `field.attributes`. Soporta mensajes traducibles |
| **Zustand** | Orquestación de alto nivel: step actual, errores de API, estado de uploads, interacciones |

### Generación dinámica del schema Zod

```ts
function buildZodSchema(fields: FieldDTO[], t: (key: string) => string): z.ZodObject<any> {
  const shape = fields.reduce((acc, field) => {
    let validator = inferBaseValidator(field.type)
    // z.string() para texto/selección, z.number() para number,
    // z.array() para multilist/checkbox múltiple, etc.

    if (field.attributes.required) {
      validator = validator.min(1, { message: t('validation.required') })
    }
    if (!field.attributes.required) {
      validator = validator.optional()
    }
    if (field.type === 'email') {
      validator = (validator as z.ZodString).email({ message: t('validation.email') })
    }

    acc[field.tag] = validator
    return acc
  }, {} as Record<string, z.ZodTypeAny>)

  return z.object(shape)
}
```

### Integración en `FormRenderer`

```tsx
const allFields = extractAllFields(schema)  // aplana sections→groups→fields

const form = useForm({
  resolver: zodResolver(buildZodSchema(allFields, t)),
  defaultValues: preloadedValues ?? buildDefaultValues(schema),
})

// FormProvider de RHF envuelve todo el árbol
// Los field components usan useFormContext() — sin prop drilling
return (
  <FormProvider {...form}>
    <FormStoreProvider>
      <FormRenderStrategy schema={schema} />
    </FormStoreProvider>
  </FormProvider>
)
```

### Traducciones en mensajes Zod

```ts
// t() recibe el locale del prop y resuelve mensajes
// Ejemplo con i18next o un diccionario simple:
const t = useTranslation(locale)
buildZodSchema(allFields, t)
```

---

## 8. Traducciones (campo `translations` del JSON)

Cada nivel del JSON tiene un objeto `translations` con el texto localizado.

```ts
function useFieldLabel(field: FieldDTO, locale: string) {
  const t = field.translations?.[locale]
  return {
    name:        t?.name        ?? field.name,
    description: t?.description ?? field.description,
    placeholder: t?.placeholder ?? field.placeholder,
  }
}

// Mismo patrón para sections y groups
function useSectionLabel(section: SectionDTO, locale: string) {
  const t = section.translations?.[locale]
  return {
    name:        t?.name        ?? section.name,
    description: t?.description ?? section.description,
  }
}
```

El `locale` fluye como prop desde `FormRenderer` hacia abajo via context — no se pasa por cada componente.

---

## 9. Sistema de Interacciones (`field.interactions`)

Cada field puede declarar un array `interactions` con acciones reactivas.

### Tipos

```ts
interface FieldInteraction {
  trigger: 'change' | 'input' | 'blur' | 'focus'
  action: string                          // nombre de la acción a ejecutar
  target: string | string[] | null        // tag(s) afectados, null = el propio field
  condition: InteractionCondition         // {} = siempre ejecutar
  params: Record<string, unknown>
}

interface InteractionCondition {
  operator?: 'equals' | 'not_equals' | 'in' | 'not_in' | 'truthy' | 'falsy'
  value?: unknown
}
```

### Acciones Built-in

| Acción | Descripción | Params clave |
|--------|-------------|--------------|
| `toggle_visibility` | Muestra/oculta los campos `target` | — |
| `toggle_required` | Hace requerido u opcional los campos `target` | — |
| `set_value` | Asigna un valor fijo o copia de otro campo | `value`, `copy_from` |
| `filter_options` | Filtra las opciones de un campo lista | `mode: 'server'\|'client'`, `filter_param`, `filter_key` |
| `ajax_validate` | Valida el valor contra un endpoint HTTP | `endpoint`, `method`, `debounce` |
| `set_date_constraint` | Ajusta el min/max de un campo fecha | `constraint: 'min'\|'max'` |
| `compute` | Calcula un valor desde una expresión matemática | `expression: '{price}*{qty}'`, `sources`, `decimals` |

### Arquitectura React

**Hook central**
```ts
function useFieldInteractions(
  field: FieldDTO,
  form: UseFormReturn,
  store: FormRendererStore
): {
  onBlur: () => void
  onFocus: () => void
  // 'change' e 'input' son observados internamente via RHF watch + debounce
}
```

**Evaluador de condición (función pura)**
```ts
function evaluateCondition(value: unknown, condition: InteractionCondition): boolean
// Soporta: equals, not_equals, in (array), not_in, truthy, falsy
```

**Ejecutores (patrón Strategy)**
```ts
type ActionExecutor = (ctx: ActionContext) => void | Promise<void>

interface ActionContext {
  interaction: FieldInteraction
  currentValue: unknown
  form: UseFormReturn
  store: FormRendererStore
  onLoadOptions?: FormRendererProps['onLoadOptions']
}

const builtInExecutors: Record<string, ActionExecutor> = {
  toggle_visibility:    toggleVisibilityExecutor,
  toggle_required:      toggleRequiredExecutor,
  set_value:            setValueExecutor,
  filter_options:       filterOptionsExecutor,   // mode=server → onLoadOptions, mode=client → filtra por option.data
  ajax_validate:        ajaxValidateExecutor,     // implementado como .refine() async en Zod + debounce
  set_date_constraint:  setDateConstraintExecutor,
  compute:              computeExecutor,          // usa expr-eval, no eval()
}
```

**Extensibilidad**: `FormRenderer` acepta `interactionHandlers` prop para registrar nuevas acciones sin tocar el renderer. Mismo patrón Strategy que el backend PHP.

### `toggle_required` y schema dinámico

Cuando `toggle_required` se dispara, el schema Zod se reconstruye con los campos actualmente visibles y requeridos, y se llama `form.trigger()` para re-validar en tiempo real.

### `compute` — expresiones seguras

Expresión: `"{price} * {quantity}"`. Flujo:
1. Regex replace `{tag}` → valor numérico del campo correspondiente
2. Evalúa con `expr-eval` (sin `eval()`)
3. Aplica `decimals` si está definido en params

---

## 10. Zustand Store (orquestación)

Un store **por instancia** de `FormRenderer` usando `createStore` + Context. No es singleton.

```ts
interface FormRendererStore {
  // Navegación multi-step
  currentStep: number
  totalSteps: number
  stepValidationMode: 'strict' | 'free'   // viene de render.metadata
  goToStep: (n: number) => void
  nextStep: () => Promise<boolean>         // en strict: valida campos del step actual primero
  prevStep: () => void

  // Submission
  isSubmitting: boolean
  setIsSubmitting: (v: boolean) => void

  // Errores del servidor por campo
  serverErrors: Record<string, string>
  setServerErrors: (errors: Record<string, string>) => void

  // Estado de uploads (eager)
  fileStates: Record<string, FileUploadState>
  setFileState: (tag: string, state: FileUploadState) => void

  // Estado de interacciones reactivas
  hiddenFields: Set<string>
  setFieldVisibility: (tag: string, visible: boolean) => void

  dynamicOptions: Record<string, OptionDTO[]>
  setDynamicOptions: (tag: string, options: OptionDTO[]) => void

  fieldLoadingOptions: Set<string>
  setFieldLoadingOptions: (tag: string, loading: boolean) => void
}

type FileUploadState =
  | { status: 'idle' }
  | { status: 'uploading'; progress?: number }
  | { status: 'done'; url: string }
  | { status: 'error'; message: string }
```

---

## 11. Submit Flow

```
1. Usuario hace click en Submit
2. RHF ejecuta el resolver Zod con los valores actuales
3. Si hay errores → los muestra en los campos, bloquea el submit
4. Si es válido → llama al handleSubmit interno
5. Los campos file ya tienen su URL en values[tag] (subidos eager)
6. Se llama onSubmit(values) con objeto plano { [field.tag]: value }
7. Si el backend responde con errores → setServerErrors({ [tag]: mensaje })
8. Los errores de servidor se muestran bajo cada campo afectado
```

---

## 12. FileField — Flujo Eager Upload

```
1. Usuario selecciona un archivo en el FileField
2. FileField llama onFileUpload(file, field.tag)
3. Store: fileStates[tag] = { status: 'uploading' }
4. FlyonUI muestra spinner / barra de progreso en el componente
5. Backend responde con la URL del archivo
6. Store: fileStates[tag] = { status: 'done', url }
7. form.setValue(field.tag, url)   ← RHF registra la URL como valor del campo
8. En submit: values[field.tag] === 'https://cdn.example.com/...'
```

Si el upload falla:
```
Store: fileStates[tag] = { status: 'error', message: '...' }
FlyonUI muestra el mensaje de error con opción de reintentar
```

---

## 13. Navegación en Stepper / Wizard

| Tipo | Modo | Comportamiento |
|------|------|----------------|
| `wizard` | Siempre strict | No se puede avanzar con errores en el step actual |
| `stepper` | Según `render.metadata.validation` | `strict` o `free` |
| `tabs` (form/section/group) | Siempre free | Navegación libre |

```ts
// nextStep en modo strict
async function nextStep(): Promise<boolean> {
  const currentFields = getFieldTagsForStep(currentStep)
  const valid = await form.trigger(currentFields)
  if (!valid) return false
  setCurrentStep(currentStep + 1)
  return true
}

// nextStep en modo free
function nextStep() {
  setCurrentStep(currentStep + 1)
}
```

---

## 14. Mapeo FlyonUI → Tipos de Campo

> Los prompts/templates HTML específicos de FlyonUI para cada campo serán entregados por el usuario campo a campo durante la implementación.

| Tipo de campo | Componente FlyonUI | Params clave |
|---------------|--------------------|--------------|
| `string`      | Input text (`input`) | `size`, `label_style` |
| `email`       | Input type="email" | `size`, `label_style` |
| `phone`       | Input type="tel" | `size`, `label_style` |
| `number`      | `input-number` (`data-input-number`) | `size`, `min`, `max`, `step`, `button_layout` |
| `password`    | `strong-password` + toggle (`data-strong-password`) | `size`, `show_strength`, `min_length`, `checks_exclude` |
| `textarea`    | Textarea (`textarea`) | `size`, `label_style`, `icon_leading`, `icon_trailing` |
| `select`      | `advanced-select` (`data-select`) | `size`, `multiple`, `searchable`, `tags_mode`, `search_limit`, `min_search_length`, `max_count_items` |
| `radio`       | Radio group | `size`, `color`, `variant`, `layout` |
| `checkbox`    | Checkbox group | `size`, `color`, `layout` |
| `switch`      | Toggle / Switch | `size`, `color`, `variant`, `layout` |
| `rating`      | Rating component | `size`, `color`, `max_stars`, `half_star` |
| `range`       | Range input (`range`, `range-{color}`) | `size`, `color`, `min`, `max`, `step`, `show_steps` |
| `combobox`    | `combo-box` (`data-combo-box`) | `size`, `open_on_focus`, `min_search_length`, `api_url` |
| `pin`         | `pin-input` (`data-pin-input`) | `size`, `variant`, `length`, `input_type`, `chars_pattern` |
| `tree`        | `tree-view` (`data-tree-view`, `controlBy: "checkbox"`) | `selection_mode`, `auto_select_children`, `expanded_by_default` |
| `duallist`    | Custom dual-panel (sin referencia FlyonUI directa) | `size`, `searchable`, `source_label`, `target_label`, `show_move_all_buttons` |
| `date`        | Input type="date" o FlyonUI Datepicker | `size` |
| `datetime`    | Input type="datetime-local" o FlyonUI Datepicker | `size` |
| `date-range`  | Dos inputs date (from/to) o FlyonUI Range picker | `size` |
| `file`        | Input type="file" + overlay de estado de upload | `size`, `label_style`, `multiple` |
| `hidden`      | input type="hidden" (no renderizado visualmente) | — |

### FieldType params — comportamiento base

Todos los tipos reciben los params de `AbstractFieldType` como mínimo (`size: 'md'`, `label_style: 'default'`). El resolver mezcla los defaults del tipo con los params almacenados en DB — DB siempre gana (`array_merge($defaults, $storedParams)`).

### `option.data` — campo de extensibilidad

Cada `OptionDTO` tiene un campo `data: object` (default `{}`) que sirve para dos propósitos:

1. **Data-attrs para interactions**: pares clave-valor arbitrarios que el frontend aplica como `data-*` en el DOM, habilitando `filter_options` en modo `client`.
   ```json
   { "value": "rm", "text": "Región Metropolitana", "data": { "country_id": "cl", "group": "Centro" } }
   ```

2. **Children para `tree`**: la jerarquía del árbol se almacena en `data.children` como array recursivo de opciones. `data.is_dir` distingue nodos rama de nodos hoja.
   ```json
   {
     "value": "docs", "text": "Documentos",
     "data": {
       "is_dir": true,
       "children": [
         { "value": "docs_contracts", "text": "Contratos", "data": { "is_dir": false } }
       ]
     }
   }
   ```

Los FieldTypes que no usan `data` simplemente lo omiten — el frontend lo trata como `{}`.

### `select` — migración de tipos legacy

| Tipo anterior | Equivalente `select` |
|--------------|----------------------|
| `list`       | `select` con defaults (`multiple: false`) |
| `list-group` | `select` + grouping via `option.data.group` |
| `multilist`  | `select` con `params.multiple: true` |

### `tree` — modo radio

FlyonUI tree-view solo soporta `controlBy: "checkbox"` nativo. El modo `selection_mode: 'radio'` es gestionado por el frontend limitando la selección a un único nodo.

### `combobox` — options estáticas vs API

Si `params.api_url` está presente, el frontend lo usa para cargar opciones bajo demanda. Si está ausente, usa el array `field.options` del schema (estático).

### Estructura base de un campo con FlyonUI

Todos los campos comparten esta estructura FlyonUI de label + input + error:

```html
<!-- Wrapper de campo -->
<div class="form-control">
  <label class="label">
    <span class="label-text">{{ field.name }}</span>
    <!-- badge "requerido" si field.attributes.required -->
  </label>

  <!-- Componente FlyonUI específico del tipo -->
  <input class="input" ... />

  <!-- Descripción opcional -->
  <span class="label-text-alt text-base-content/60">{{ field.description }}</span>

  <!-- Error de validación (RHF/Zod o serverErrors) -->
  <span class="label-text-alt text-error">{{ error.message }}</span>
</div>
```

---

## 15. FieldRenderer — Dispatcher

```tsx
const FIELD_COMPONENTS: Record<string, React.ComponentType<FieldProps>> = {
  string:       StringField,
  email:        EmailField,
  phone:        PhoneField,
  number:       NumberField,
  password:     PasswordField,
  textarea:     TextareaField,
  date:         DateField,
  datetime:     DateTimeField,
  'date-range': DateRangeField,
  select:       SelectField,
  radio:        RadioField,
  checkbox:     CheckboxField,
  switch:       SwitchField,
  rating:       RatingField,
  range:        RangeField,
  combobox:     ComboboxField,
  pin:          PinField,
  tree:         TreeField,
  duallist:     DuallistField,
  file:         FileField,
  hidden:       HiddenField,
}

function FieldRenderer({ field }: { field: FieldDTO }) {
  const { hiddenFields } = useFormStore()
  if (hiddenFields.has(field.tag)) return null
  if (!field.enabled) return null

  const Component = FIELD_COMPONENTS[field.type]
  if (!Component) return null  // tipo desconocido — ignorar silenciosamente

  return <Component field={field} />
}
```

---

## 16. Props comunes de Field Components

```ts
interface FieldProps {
  field: FieldDTO
  // Acceden a RHF via useFormContext()
  // Acceden al store via useFormStore()
  // Acceden al locale via useFormLocale()
}
```

Cada field component internamente:
1. Llama `useFieldLabel(field, locale)` para obtener el label localizado
2. Llama `useFormContext()` para registrarse en RHF
3. Llama `useFieldInteractions(field, form, store)` para interacciones reactivas
4. Renderiza el componente FlyonUI correspondiente

---

## 17. Estructura de Archivos

```
src/
  components/
    form-renderer/
      FormRenderer.tsx                ← entry point + FormProvider (RHF) + FormStoreProvider
      form-renderer.store.ts          ← createStore Zustand + FormStoreContext
      form-renderer.context.ts        ← locale context, schema context
      form-renderer.types.ts          ← todos los tipos TS (FormDTO, FieldDTO, OptionDTO, etc.)
      │
      schema/
        zod-schema-builder.ts         ← buildZodSchema(fields, t)
        default-values-builder.ts     ← buildDefaultValues(schema)
      │
      renders/
        form/
          SimpleFormRender.tsx
          StepperFormRender.tsx        ← FlyonUI Steps component
          TabsFormRender.tsx           ← FlyonUI Tabs component
          WizardFormRender.tsx         ← FlyonUI Steps, siempre strict
        section/
          SimpleSectionRender.tsx
          AccordionSectionRender.tsx   ← FlyonUI Accordion component
          CollapsibleSectionRender.tsx ← FlyonUI Collapse component
          TabsSectionRender.tsx        ← FlyonUI Tabs component
        group/
          SimpleGroupRender.tsx
          MatrixGroupRender.tsx        ← grid CSS con rows/cols de parameters
          TabsGroupRender.tsx          ← FlyonUI Tabs component
      │
      fields/
        FieldRenderer.tsx             ← dispatcher por field.type
        StringField.tsx
        EmailField.tsx
        PhoneField.tsx
        NumberField.tsx
        PasswordField.tsx
        TextareaField.tsx
        DateField.tsx
        DateTimeField.tsx
        DateRangeField.tsx
        SelectField.tsx               ← advanced-select (FlyonUI data-select)
        RadioField.tsx
        CheckboxField.tsx
        SwitchField.tsx
        RatingField.tsx
        RangeField.tsx
        ComboboxField.tsx             ← FlyonUI data-combo-box
        PinField.tsx                  ← FlyonUI data-pin-input
        TreeField.tsx                 ← FlyonUI data-tree-view (controlBy: "checkbox")
        DuallistField.tsx             ← custom dual-panel component
        FileField.tsx                 ← eager upload + FlyonUI upload state UI
        HiddenField.tsx
      │
      interactions/
        useFieldInteractions.ts       ← hook central
        evaluateCondition.ts          ← evaluador puro de condiciones
        executors/
          toggleVisibility.ts
          toggleRequired.ts
          setValue.ts
          filterOptions.ts
          ajaxValidate.ts
          setDateConstraint.ts
          compute.ts
      │
      hooks/
        useFieldLabel.ts              ← resolución de translations por locale
        useFormStore.ts               ← hook tipado para el store Zustand
        useFormLocale.ts              ← hook para acceder al locale del context
      │
      utils/
        extractAllFields.ts           ← aplana sections→groups→fields
        getFieldTagsForStep.ts        ← retorna tags de campos del step N
        expr-evaluator.ts             ← wrapper de expr-eval para compute
```

---

## 18. Tipos TypeScript Clave

```ts
// form-renderer.types.ts

export interface FormDTO {
  id: string
  name: string
  tag: string
  locale: string
  default_locale: string
  enabled: boolean
  parameters: Record<string, unknown>
  render: { type: FormRenderType; metadata: Record<string, unknown> }
  translations: Record<string, { name: string; description?: string }> | null
  sections: SectionDTO[]
}

export type FormRenderType = 'simple' | 'stepper' | 'tabs' | 'wizard'

export interface SectionDTO {
  id: string
  name: string
  tag: string
  description: string | null
  position: number
  enabled: boolean
  parameters: Record<string, unknown>
  render: { type: SectionRenderType; metadata: Record<string, unknown> }
  translations: Record<string, { name: string; description?: string }> | null
  groups: GroupDTO[]
}

export type SectionRenderType = 'simple' | 'accordion' | 'collapsible' | 'tabs'

export interface GroupDTO {
  id: string
  name: string
  tag: string
  description: string | null
  position: number
  enabled: boolean
  parameters: Record<string, unknown>
  render: { type: GroupRenderType; metadata: Record<string, unknown> }
  translations: Record<string, { name: string; description?: string }> | null
  fields: FieldDTO[]
}

export type GroupRenderType = 'simple' | 'matrix' | 'tabs'

export interface FieldDTO {
  id: string
  name: string
  tag: string
  type: FieldType
  description: string | null
  position: number
  enabled: boolean
  placeholder: string | null
  default_value: unknown
  style: Record<string, unknown>
  attributes: FieldAttributes
  parameters: Record<string, unknown>
  interactions: FieldInteraction[]
  translations: Record<string, {
    name?: string
    description?: string
    placeholder?: string
  }> | null
  options: (OptionDTO | OptionGroupDTO)[]
}

export type FieldType =
  | 'string' | 'email' | 'phone' | 'number' | 'password'
  | 'textarea' | 'date' | 'datetime' | 'date-range'
  | 'select' | 'radio' | 'checkbox' | 'switch' | 'rating'
  | 'range' | 'combobox' | 'pin' | 'tree' | 'duallist'
  | 'file' | 'hidden'

export interface FieldAttributes {
  required: boolean
  readonly: boolean
  show: boolean
  create: boolean
  edit: boolean
  [key: string]: unknown
}

export interface OptionDTO {
  value: string | number
  text: string
  tag: string | null
  icon: string | null
  color: string | null
  position: number
  data: Record<string, unknown>   // data-attrs para interactions; `data.children` para tree
}

export interface OptionGroupDTO {
  text: string
  options: OptionDTO[]
}

export interface FieldInteraction {
  trigger: 'change' | 'input' | 'blur' | 'focus'
  action: string
  target: string | string[] | null
  condition: InteractionCondition
  params: Record<string, unknown>
}

export interface InteractionCondition {
  operator?: 'equals' | 'not_equals' | 'in' | 'not_in' | 'truthy' | 'falsy'
  value?: unknown
}

export type InteractionHandler = (ctx: ActionContext) => void | Promise<void>

export interface ActionContext {
  interaction: FieldInteraction
  currentValue: unknown
  form: import('react-hook-form').UseFormReturn
  store: FormRendererStore
  onLoadOptions?: (tag: string, params: Record<string, unknown>) => Promise<OptionDTO[]>
}

export type FileUploadState =
  | { status: 'idle' }
  | { status: 'uploading'; progress?: number }
  | { status: 'done'; url: string }
  | { status: 'error'; message: string }
```

---

## 19. Dependencias Requeridas

```json
{
  "react-hook-form": "^7.x",
  "@hookform/resolvers": "^3.x",
  "zod": "^3.x",
  "zustand": "^5.x",
  "expr-eval": "^2.x"
}
```

> FlyonUI ya debe estar instalado y configurado en el proyecto.

---

## 20. Open Questions (iteraciones futuras)

| Tema | Estado | Descripción |
|------|--------|-------------|
| Context mode (create/edit/show) | Postergado | Backend filtra campos por contexto — frontend no necesita prop mode por ahora |
| `attributes.filter` | Postergado | Tiene un contexto diferente (módulo de filtros), no es visibilidad dinámica de campo |
| Draft / auto-save | Postergado | Guardar estado en localStorage entre sesiones |
| Custom field overrides | Postergado | Prop para pasar un componente custom que sobrescriba un tipo de campo |
| RTL support | Postergado | Soporte right-to-left para otras locales |
| Accesibilidad (ARIA) | Postergado | Labels aria-describedby para errores, roles en tabs/accordion |

---

## 21. Orden de Implementación

1. **`form-renderer.types.ts`** — todos los tipos TypeScript (incluye FieldInteraction, FileUploadState)
2. **`form-renderer.store.ts`** — Zustand store con createStore + context (incluye estado de interacciones)
3. **`zod-schema-builder.ts` + `default-values-builder.ts`**
4. **`FormRenderer.tsx`** — entry point, FormProvider (RHF), FormStoreProvider, locale context
5. **Form renders** — SimpleFormRender primero, luego Tabs, Stepper, Wizard
6. **Section renders** — Simple → Accordion → Collapsible → Tabs
7. **Group renders** — Simple → Matrix → Tabs
8. **`FieldRenderer.tsx`** — dispatcher (con chequeo de `hiddenFields` del store)
9. **Field components** — campo por campo (se solicita el prompt FlyonUI al usuario antes de cada uno): `string`, `email`, `phone`, `number`, `password`, `textarea`, `date`, `datetime`, `date-range`, `select`, `radio`, `checkbox`, `switch`, `rating`, `range`, `combobox`, `pin`, `tree`, `duallist`, `file`, `hidden`
10. **Sistema de interacciones** — `evaluateCondition` → executors → `useFieldInteractions`
11. **`FileField.tsx`** — flujo eager upload completo con estados FlyonUI
