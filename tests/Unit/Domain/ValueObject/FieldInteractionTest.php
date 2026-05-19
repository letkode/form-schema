<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Domain\ValueObject;

use Letkode\FormSchema\Domain\ValueObject\FieldInteraction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FieldInteractionTest extends TestCase
{
    #[Test]
    public function testFromArrayWithMinimalData(): void
    {
        $interaction = FieldInteraction::fromArray([
            'trigger' => 'change',
            'action' => 'toggle_visibility',
        ]);

        self::assertSame('change', $interaction->trigger);
        self::assertSame('toggle_visibility', $interaction->action);
        self::assertNull($interaction->target);
        self::assertSame([], $interaction->condition);
        self::assertSame([], $interaction->params);
    }

    #[Test]
    public function testFromArrayWithFullData(): void
    {
        $interaction = FieldInteraction::fromArray([
            'trigger' => 'blur',
            'action' => 'ajax_validate',
            'target' => null,
            'condition' => [],
            'params' => ['endpoint' => '/api/check', 'method' => 'GET', 'debounce' => 300],
        ]);

        self::assertSame('blur', $interaction->trigger);
        self::assertSame('ajax_validate', $interaction->action);
        self::assertNull($interaction->target);
        self::assertSame('/api/check', $interaction->params['endpoint']);
        self::assertSame(300, $interaction->params['debounce']);
    }

    #[Test]
    public function testTargetAsString(): void
    {
        $interaction = FieldInteraction::fromArray([
            'trigger' => 'change',
            'action' => 'filter_options',
            'target' => 'city',
            'condition' => [],
            'params' => ['mode' => 'server', 'filter_param' => 'country_id'],
        ]);

        self::assertSame('city', $interaction->target);
    }

    #[Test]
    public function testTargetAsArray(): void
    {
        $interaction = FieldInteraction::fromArray([
            'trigger' => 'change',
            'action' => 'toggle_visibility',
            'target' => ['billing_address', 'billing_city'],
            'condition' => ['operator' => 'equals', 'value' => true],
            'params' => [],
        ]);

        self::assertSame(['billing_address', 'billing_city'], $interaction->target);
        self::assertSame('equals', $interaction->condition['operator']);
    }

    #[Test]
    public function testToArrayRoundTrip(): void
    {
        $data = [
            'trigger' => 'change',
            'action' => 'compute',
            'target' => 'total',
            'condition' => [],
            'params' => ['expression' => '{price} * {quantity}', 'sources' => ['price', 'quantity'], 'decimals' => 2],
        ];

        $interaction = FieldInteraction::fromArray($data);

        self::assertSame($data, $interaction->toArray());
    }
}
