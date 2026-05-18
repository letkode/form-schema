<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Domain\ValueObject;

use Letkode\FormSchema\Domain\ValueObject\FilterRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FilterRuleTest extends TestCase
{
    #[Test]
    public function testDefaults(): void
    {
        $rule = new FilterRule();

        self::assertFalse($rule->enabled);
        self::assertNull($rule->key);
    }

    #[Test]
    public function testFromArray(): void
    {
        $rule = FilterRule::fromArray([
            'enabled' => true,
            'key' => 'country_id',
        ]);

        self::assertTrue($rule->enabled);
        self::assertSame('country_id', $rule->key);
    }

    #[Test]
    public function testToArray(): void
    {
        $rule = new FilterRule(enabled: true, key: 'country_id');

        self::assertSame([
            'enabled' => true,
            'key' => 'country_id',
        ], $rule->toArray());
    }
}
