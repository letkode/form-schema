<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Domain\ValueObject;

use Letkode\FormSchema\Domain\ValueObject\FieldAttributes;
use Letkode\FormSchema\Domain\ValueObject\FilterRule;
use Letkode\FormSchema\Domain\ValueObject\UniqueRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FieldAttributesTest extends TestCase
{
    #[Test]
    public function testDefaultValues(): void
    {
        $attrs = FieldAttributes::default();

        self::assertFalse($attrs->required);
        self::assertFalse($attrs->readonly);
        self::assertSame([], $attrs->actions);
    }

    #[Test]
    public function testFromArrayWithKnownKeys(): void
    {
        $attrs = FieldAttributes::fromArray(['required' => true]);

        self::assertTrue($attrs->required);
        self::assertFalse($attrs->readonly);
    }

    #[Test]
    public function testFromArrayCapturesDynamicKeys(): void
    {
        $attrs = FieldAttributes::fromArray(['required' => true, 'preview' => false]);

        self::assertFalse($attrs->get('preview'));
    }

    #[Test]
    public function testToArrayIsFlat(): void
    {
        $attrs = FieldAttributes::default();
        $array = $attrs->toArray();

        self::assertArrayNotHasKey('dynamic', $array);
        self::assertArrayNotHasKey('extra', $array);
        self::assertArrayHasKey('required', $array);
        self::assertArrayHasKey('readonly', $array);
        self::assertArrayHasKey('actions', $array);
    }

    #[Test]
    public function testToArrayIncludesDynamicKeys(): void
    {
        $attrs = FieldAttributes::fromArray(['required' => true, 'preview' => false, 'custom_flag' => 'yes']);
        $array = $attrs->toArray();

        self::assertArrayHasKey('preview', $array);
        self::assertFalse($array['preview']);
        self::assertSame('yes', $array['custom_flag']);
    }

    #[Test]
    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        $attrs = FieldAttributes::default();

        self::assertSame('fallback', $attrs->get('nonexistent', 'fallback'));
    }

    #[Test]
    public function testUniqueRuleFromArray(): void
    {
        $attrs = FieldAttributes::fromArray([
            'unique' => ['enabled' => true, 'entity' => 'App\\Entity\\User', 'method' => 'findByEmail'],
        ]);

        self::assertInstanceOf(UniqueRule::class, $attrs->unique);
        self::assertTrue($attrs->unique->enabled);
        self::assertSame('App\\Entity\\User', $attrs->unique->entity);
        self::assertSame('findByEmail', $attrs->unique->method);
    }

    #[Test]
    public function testFilterRuleFromArray(): void
    {
        $attrs = FieldAttributes::fromArray([
            'filter' => ['enabled' => true, 'key' => 'country_id'],
        ]);

        self::assertInstanceOf(FilterRule::class, $attrs->filter);
        self::assertTrue($attrs->filter->enabled);
        self::assertSame('country_id', $attrs->filter->key);
    }

    #[Test]
    public function testActionsDefaultsToEmpty(): void
    {
        self::assertSame([], FieldAttributes::default()->toArray()['actions']);
    }

    #[Test]
    public function testFromArrayParsesActions(): void
    {
        $attrs = FieldAttributes::fromArray([
            'actions' => ['edit' => ['enabled' => false, 'required' => true]],
        ]);

        self::assertSame(['edit' => ['enabled' => false, 'required' => true]], $attrs->actions);
    }

    #[Test]
    public function testWithActionOverridesAppliesRequiredOverride(): void
    {
        $attrs = FieldAttributes::fromArray([
            'required' => false,
            'actions' => ['edit' => ['enabled' => true, 'required' => true]],
        ]);

        $merged = $attrs->withActionOverrides('edit');

        self::assertTrue($merged->required);
        self::assertSame($attrs->actions, $merged->actions);
    }

    #[Test]
    public function testWithActionOverridesSkipsUnknownContext(): void
    {
        $attrs = FieldAttributes::fromArray(['required' => false]);
        $result = $attrs->withActionOverrides('nonexistent');

        self::assertSame($attrs, $result);
    }
}
