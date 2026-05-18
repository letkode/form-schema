<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\FieldType;

use Letkode\FormSchema\Infrastructure\FieldType\CheckboxFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\DateFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\DateRangeFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\DateTimeFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\EmailFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\FileFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\HiddenFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\ListFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\ListGroupFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\MultilistFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\NumberFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\PasswordFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\PhoneFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\RadioFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\RatingFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\StringFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\SwitchFieldType;
use Letkode\FormSchema\Infrastructure\FieldType\TextareaFieldType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FieldTypeTest extends TestCase
{
    #[Test]
    public function testStringFieldTypeDoesNotTakeOptions(): void
    {
        self::assertFalse(new StringFieldType()->takesOptions());
    }

    #[Test]
    public function testListFieldTypeTakesOptions(): void
    {
        self::assertTrue(new ListFieldType()->takesOptions());
    }

    #[Test]
    public function testSwitchFieldTypeTakesOptions(): void
    {
        self::assertTrue(new SwitchFieldType()->takesOptions());
    }

    #[Test]
    public function testDateFieldTypeFormatsModifier(): void
    {
        $result = new DateFieldType()->formatDefaultValue('+1 day');

        self::assertIsString($result);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);
    }

    #[Test]
    public function testDateFieldTypeReturnsNullForNull(): void
    {
        self::assertNull(new DateFieldType()->formatDefaultValue(null));
    }

    #[Test]
    public function testDateRangeFieldTypeReturnsArray(): void
    {
        $result = new DateRangeFieldType()->formatDefaultValue(null);

        self::assertIsArray($result);
        self::assertArrayHasKey('from', $result);
        self::assertArrayHasKey('to', $result);
        self::assertNull($result['from']);
        self::assertNull($result['to']);
    }

    #[Test]
    public function testAllFieldTypesHaveUniqueName(): void
    {
        $types = [
            new CheckboxFieldType(),
            new DateFieldType(),
            new DateRangeFieldType(),
            new DateTimeFieldType(),
            new EmailFieldType(),
            new FileFieldType(),
            new HiddenFieldType(),
            new ListFieldType(),
            new ListGroupFieldType(),
            new MultilistFieldType(),
            new NumberFieldType(),
            new PasswordFieldType(),
            new PhoneFieldType(),
            new RadioFieldType(),
            new RatingFieldType(),
            new StringFieldType(),
            new SwitchFieldType(),
            new TextareaFieldType(),
        ];

        $names = array_map(static fn ($t) => $t::getName(), $types);
        $unique = array_unique($names);

        self::assertCount(18, $types);
        self::assertCount(\count($names), $unique, 'Duplicate field type names found: ' . implode(', ', array_diff_assoc($names, $unique)));
    }
}
