<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\Resolver;

use Letkode\FormSchema\Application\DTO\FieldDTO;
use Letkode\FormSchema\Application\DTO\FormDTO;
use Letkode\FormSchema\Application\DTO\GroupDTO;
use Letkode\FormSchema\Application\DTO\InteractionDTO;
use Letkode\FormSchema\Application\DTO\SectionDTO;
use Letkode\FormSchema\Application\Filter\FilterCriteria;
use Letkode\FormSchema\Application\Filter\StructureFilter;
use Letkode\FormSchema\Domain\Contract\FormSchemaResolverInterface;
use Letkode\FormSchema\Domain\Contract\InteractionHandlerRegistryInterface;
use Letkode\FormSchema\Domain\Exception\FormNotFoundException;
use Letkode\FormSchema\Domain\Repository\FormRepositoryInterface;
use Letkode\FormSchema\Domain\ValueObject\FieldAttributes;
use Letkode\FormSchema\Domain\ValueObject\FieldInteraction;
use Letkode\FormSchema\Infrastructure\Registry\FieldTypeRegistry;
use Letkode\FormSchema\Infrastructure\Registry\FormRenderRegistry;
use Letkode\FormSchema\Infrastructure\Registry\GroupRenderRegistry;
use Letkode\FormSchema\Infrastructure\Registry\SectionRenderRegistry;

final class FormSchemaResolver implements FormSchemaResolverInterface
{
    private string|null $tag = null;
    private string|null $locale = null;
    private string|null $context = null;
    private array $includeSections = [];
    private array $excludeSections = [];
    private array $includeGroups = [];
    private array $excludeGroups = [];

    public function __construct(
        private readonly FormRepositoryInterface $formRepository,
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly OptionsResolver $optionsResolver,
        private readonly FormRenderRegistry $formRenderRegistry,
        private readonly SectionRenderRegistry $sectionRenderRegistry,
        private readonly GroupRenderRegistry $groupRenderRegistry,
        private readonly InteractionHandlerRegistryInterface $interactionHandlerRegistry,
        private readonly string $defaultLocale = 'es',
    ) {
    }

    #[\Override]
    public function schema(string $tag): static
    {
        $clone = clone $this;
        $clone->tag = $tag;

        return $clone;
    }

    #[\Override]
    public function withLocale(string $locale): static
    {
        $clone = clone $this;
        $clone->locale = $locale;

        return $clone;
    }

    #[\Override]
    public function withContext(string $action): static
    {
        $clone = clone $this;
        $clone->context = $action;

        return $clone;
    }

    #[\Override]
    public function includingSections(array $tags): static
    {
        $clone = clone $this;
        $clone->includeSections = $tags;

        return $clone;
    }

    #[\Override]
    public function excludingSections(array $tags): static
    {
        $clone = clone $this;
        $clone->excludeSections = $tags;

        return $clone;
    }

    #[\Override]
    public function includingGroups(array $tags): static
    {
        $clone = clone $this;
        $clone->includeGroups = $tags;

        return $clone;
    }

    #[\Override]
    public function excludingGroups(array $tags): static
    {
        $clone = clone $this;
        $clone->excludeGroups = $tags;

        return $clone;
    }

    #[\Override]
    public function resolve(): FormDTO
    {
        $form = $this->formRepository->findOneByTag($this->tag ?? '')
            ?? throw new FormNotFoundException($this->tag ?? '');

        $filter = new StructureFilter(new FilterCriteria(
            includeSections: $this->includeSections,
            excludeSections: $this->excludeSections,
            includeGroups: $this->includeGroups,
            excludeGroups: $this->excludeGroups,
        ));

        $effectiveLocale = $this->locale ?? $form->defaultLang ?? $this->defaultLocale;

        $form->withActiveLocale($effectiveLocale);

        $formRenderType = $form->parameters['type_render'] ?? 'default';
        $formRender = $this->formRenderRegistry->get($formRenderType);
        $formMeta = $formRender->renderMeta($form->parameters);

        $sectionDTOs = [];
        $allowedSections = $filter->filterSections($form->sections);

        foreach ($allowedSections as $section) {
            if (!$section->enabled) {
                continue;
            }

            $sectionRenderType = $section->parameters['type_render'] ?? 'default';
            $sectionRender = $this->sectionRenderRegistry->get($sectionRenderType);
            $sectionMeta = $sectionRender->renderMeta($section->parameters);

            $groupDTOs = [];
            $allowedGroups = $filter->filterGroups($section->groups);

            foreach ($allowedGroups as $group) {
                if (!$group->enabled) {
                    continue;
                }

                $groupRenderType = $group->parameters['type_render'] ?? 'default';
                $groupRender = $this->groupRenderRegistry->get($groupRenderType);
                $groupMeta = $groupRender->renderMeta($group->parameters);

                $fieldDTOs = [];

                foreach ($group->fields as $field) {
                    if (!$field->enabled) {
                        continue;
                    }

                    $fieldType = $this->fieldTypeRegistry->get($field->type);
                    $defaultAttrs = $fieldType->getDefaultAttributes();
                    $attrs = FieldAttributes::fromArray(
                        array_replace_recursive($defaultAttrs->toArray(), $field->attributes),
                    );

                    if (null !== $this->context) {
                        $actionConfig = $attrs->toArray()['actions'][$this->context] ?? [];
                        if (false === ($actionConfig['enabled'] ?? true)) {
                            continue;
                        }
                        $attrs = $attrs->withActionOverrides($this->context);
                    }

                    $options = [];
                    if ($fieldType->takesOptions()) {
                        $setOptions = $field->parameters['set_options'] ?? [];
                        $options = $this->optionsResolver->resolve($setOptions, $effectiveLocale);
                    }

                    $defaultValue = $fieldType->formatDefaultValue($field->parameters['default_value'] ?? null);

                    $placeholder = $field->getTranslation($effectiveLocale, 'placeholder')
                        ?? $field->parameters['placeholder']
                        ?? null;

                    $interactions = array_map(
                        function (array $raw): InteractionDTO {
                            $interaction = FieldInteraction::fromArray($raw);
                            $handler = $this->interactionHandlerRegistry->get($interaction->action);
                            $mergedParams = array_replace($handler->getDefaultParams(), $interaction->params);

                            return InteractionDTO::fromInteraction($interaction, $mergedParams);
                        },
                        $field->interactions ?? [],
                    );

                    $storedParams = array_diff_key($field->parameters, array_flip(['set_options', 'default_value', 'placeholder', 'style']));
                    $mergedParams = array_merge($fieldType->getDefaultParams(), $storedParams);

                    $fieldDTOs[] = new FieldDTO(
                        id: (string) $field->uuid,
                        name: $field->name,
                        tag: $field->tag,
                        type: $field->type,
                        description: $field->description,
                        attributes: $attrs->toArray(),
                        parameters: $mergedParams,
                        position: $field->position,
                        enabled: $field->enabled,
                        placeholder: $placeholder,
                        defaultValue: $defaultValue,
                        style: $field->parameters['style'] ?? [],
                        options: $options,
                        translations: $field->translations,
                        interactions: $interactions,
                    );
                }

                $groupDTOs[] = new GroupDTO(
                    id: (string) $group->uuid,
                    name: $group->name,
                    tag: $group->tag,
                    description: $group->description,
                    position: $group->position,
                    enabled: $group->enabled,
                    parameters: $group->parameters,
                    renderType: $groupRenderType,
                    renderMeta: $groupMeta,
                    translations: $group->translations,
                    fields: $fieldDTOs,
                );
            }

            $sectionDTOs[] = new SectionDTO(
                id: (string) $section->uuid,
                name: $section->name,
                tag: $section->tag,
                description: $section->description,
                position: $section->position,
                enabled: $section->enabled,
                parameters: $section->parameters,
                renderType: $sectionRenderType,
                renderMeta: $sectionMeta,
                translations: $section->translations,
                groups: $groupDTOs,
            );
        }

        return new FormDTO(
            id: (string) $form->uuid,
            name: $form->name,
            tag: $form->tag,
            locale: $effectiveLocale,
            defaultLocale: $form->defaultLang,
            enabled: $form->enabled,
            parameters: $form->parameters,
            renderType: $formRenderType,
            renderMeta: $formMeta,
            translations: $form->translations,
            sections: $sectionDTOs,
        );
    }

    #[\Override]
    public function toArray(): array
    {
        return $this->resolve()->jsonSerialize();
    }
}
