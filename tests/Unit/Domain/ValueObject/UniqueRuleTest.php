<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Domain\ValueObject;

use Letkode\FormSchema\Domain\ValueObject\UniqueRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UniqueRuleTest extends TestCase
{
    #[Test]
    public function testDefaults(): void
    {
        $rule = new UniqueRule();

        self::assertFalse($rule->enabled);
        self::assertNull($rule->entity);
        self::assertNull($rule->method);
    }

    #[Test]
    public function testFromArray(): void
    {
        $rule = UniqueRule::fromArray([
            'enabled' => true,
            'entity' => 'App\\Entity\\User',
            'method' => 'findByEmail',
        ]);

        self::assertTrue($rule->enabled);
        self::assertSame('App\\Entity\\User', $rule->entity);
        self::assertSame('findByEmail', $rule->method);
    }

    #[Test]
    public function testToArray(): void
    {
        $rule = new UniqueRule(enabled: true, entity: 'App\\Entity\\User', method: 'findByEmail');

        self::assertSame([
            'enabled' => true,
            'entity' => 'App\\Entity\\User',
            'method' => 'findByEmail',
        ], $rule->toArray());
    }
}
