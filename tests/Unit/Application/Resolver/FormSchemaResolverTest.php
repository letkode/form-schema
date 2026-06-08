<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Application\Resolver;

use Letkode\FormSchema\Application\DTO\FormDTO;
use Letkode\FormSchema\Application\Resolver\FormSchemaResolver;
use Letkode\FormSchema\Application\Resolver\OptionsResolver;
use Letkode\FormSchema\Domain\Entity\Form;
use Letkode\FormSchema\Domain\Entity\FormField;
use Letkode\FormSchema\Domain\Entity\FormGroup;
use Letkode\FormSchema\Domain\Entity\FormSection;
use Letkode\FormSchema\Domain\Exception\FormNotFoundException;
use Letkode\FormSchema\Domain\Repository\FormRepositoryInterface;
use Letkode\FormSchema\Infrastructure\FieldType\StringFieldType;
use Letkode\FormSchema\Infrastructure\FormRender\DefaultFormRender;
use Letkode\FormSchema\Infrastructure\GroupRender\DefaultGroupRender;
use Letkode\FormSchema\Infrastructure\Registry\FieldTypeRegistry;
use Letkode\FormSchema\Infrastructure\Registry\FormRenderRegistry;
use Letkode\FormSchema\Infrastructure\Registry\GroupRenderRegistry;
use Letkode\FormSchema\Infrastructure\Registry\InteractionHandlerRegistry;
use Letkode\FormSchema\Infrastructure\Registry\OptionsSourceRegistry;
use Letkode\FormSchema\Infrastructure\Registry\SectionRenderRegistry;
use Letkode\FormSchema\Infrastructure\SectionRender\DefaultSectionRender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FormSchemaResolverTest extends TestCase
{
    private FormRepositoryInterface&MockObject $formRepository;
    private FormSchemaResolver $resolver;

    protected function setUp(): void
    {
        $this->formRepository = $this->createMock(FormRepositoryInterface::class);

        $fieldTypeRegistry = new FieldTypeRegistry(new \ArrayIterator([new StringFieldType()]));
        $formRenderRegistry = new FormRenderRegistry(new \ArrayIterator([new DefaultFormRender()]));
        $sectionRenderRegistry = new SectionRenderRegistry(new \ArrayIterator([new DefaultSectionRender()]));
        $groupRenderRegistry = new GroupRenderRegistry(new \ArrayIterator([new DefaultGroupRender()]));
        $optionsSourceRegistry = new OptionsSourceRegistry(new \ArrayIterator([]));
        $optionsResolver = new OptionsResolver($optionsSourceRegistry);
        $interactionHandlerRegistry = new InteractionHandlerRegistry(new \ArrayIterator([]));

        $this->resolver = new FormSchemaResolver(
            formRepository: $this->formRepository,
            fieldTypeRegistry: $fieldTypeRegistry,
            optionsResolver: $optionsResolver,
            formRenderRegistry: $formRenderRegistry,
            sectionRenderRegistry: $sectionRenderRegistry,
            groupRenderRegistry: $groupRenderRegistry,
            interactionHandlerRegistry: $interactionHandlerRegistry,
            defaultLocale: 'es',
        );
    }

    private function buildSimpleForm(string $tag = 'test_form'): Form
    {
        $form = new Form();
        $form->setTag($tag);
        $form->name = 'Test Form';
        $form->enabled = true;
        $form->defaultLang = 'es';

        $section = new FormSection();
        $section->setTag('section_1');
        $section->name = 'Section 1';
        $section->enabled = true;
        $section->position = 0;

        $group = new FormGroup();
        $group->setTag('group_1');
        $group->name = 'Group 1';
        $group->enabled = true;
        $group->position = 0;

        $field = new FormField();
        $field->setTag('field_1');
        $field->name = 'Field 1';
        $field->type = 'string';
        $field->enabled = true;
        $field->position = 0;
        $field->attributes = [];
        $field->parameters = [];

        $group->addField($field);
        $section->addGroup($group);
        $form->addSection($section);

        return $form;
    }

    #[Test]
    public function testSchemaThrowsWhenTagNotSet(): void
    {
        $this->formRepository->method('findOneByTag')->willReturn(null);

        $this->expectException(FormNotFoundException::class);

        $this->resolver->resolve();
    }

    #[Test]
    public function testSchemaThrowsWhenFormNotFound(): void
    {
        $this->formRepository->method('findOneByTag')->willReturn(null);

        $this->expectException(FormNotFoundException::class);

        $this->resolver->schema('nonexistent')->resolve();
    }

    #[Test]
    public function testWithContextUnknownContextIncludesField(): void
    {
        $this->formRepository->method('findOneByTag')->willReturn($this->buildSimpleForm());

        $dto = $this->resolver->schema('test_form')->withContext('nonexistent_context')->resolve();

        $fieldDTOs = $dto->sections[0]->groups[0]->fields;
        self::assertCount(1, $fieldDTOs, 'Unknown context should not exclude fields');
    }

    #[Test]
    public function testWithContextFiltersFields(): void
    {
        $form = $this->buildSimpleForm();
        $section = $form->sections->first();
        $group = $section->groups->first();
        $field = $group->fields->first();
        $field->attributes = ['actions' => ['create' => ['enabled' => false]]];

        $this->formRepository->method('findOneByTag')->willReturn($form);

        $dto = $this->resolver->schema('test_form')->withContext('create')->resolve();

        $fieldDTOs = $dto->sections[0]->groups[0]->fields;
        self::assertEmpty($fieldDTOs, 'Field with actions.create.enabled=false should be excluded in create context');
    }

    #[Test]
    public function testWithContextAppliesRequiredOverride(): void
    {
        $form = $this->buildSimpleForm();
        $section = $form->sections->first();
        $group = $section->groups->first();
        $field = $group->fields->first();
        $field->attributes = ['required' => false, 'actions' => ['edit' => ['required' => true]]];

        $this->formRepository->method('findOneByTag')->willReturn($form);

        $dto = $this->resolver->schema('test_form')->withContext('edit')->resolve();

        $fieldDTOs = $dto->sections[0]->groups[0]->fields;
        self::assertCount(1, $fieldDTOs);
        self::assertTrue($fieldDTOs[0]->attributes['required']);
    }

    #[Test]
    public function testResolveReturnsFormDTO(): void
    {
        $this->formRepository->method('findOneByTag')->willReturn($this->buildSimpleForm());

        $dto = $this->resolver->schema('test_form')->resolve();

        self::assertInstanceOf(FormDTO::class, $dto);
        self::assertSame('test_form', $dto->tag);
        self::assertSame('es', $dto->locale);
        self::assertCount(1, $dto->sections);
    }

    #[Test]
    public function testImmutability(): void
    {
        $formA = $this->buildSimpleForm('form_a');
        $formB = $this->buildSimpleForm('form_b');

        $this->formRepository->method('findOneByTag')->willReturnMap([
            ['form_a', $formA],
            ['form_b', $formB],
        ]);

        $resolverA = $this->resolver->schema('form_a');
        $resolverB = $this->resolver->schema('form_b');

        self::assertNotSame($resolverA, $resolverB);

        $dtoA = $resolverA->resolve();
        $dtoB = $resolverB->resolve();

        self::assertSame('form_a', $dtoA->tag);
        self::assertSame('form_b', $dtoB->tag);
    }
}
