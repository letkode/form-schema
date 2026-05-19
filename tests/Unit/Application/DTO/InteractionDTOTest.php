<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Application\DTO;

use Letkode\FormSchema\Application\DTO\InteractionDTO;
use Letkode\FormSchema\Domain\ValueObject\FieldInteraction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InteractionDTOTest extends TestCase
{
    #[Test]
    public function testFromInteractionMapsCorrectly(): void
    {
        $interaction = FieldInteraction::fromArray([
            'trigger' => 'change',
            'action' => 'toggle_visibility',
            'target' => ['field_a', 'field_b'],
            'condition' => ['operator' => 'equals', 'value' => true],
            'params' => [],
        ]);

        $dto = InteractionDTO::fromInteraction($interaction);

        self::assertSame('change', $dto->trigger);
        self::assertSame('toggle_visibility', $dto->action);
        self::assertSame(['field_a', 'field_b'], $dto->target);
        self::assertSame(['operator' => 'equals', 'value' => true], $dto->condition);
    }

    #[Test]
    public function testJsonSerializeFilterOptions(): void
    {
        $dto = InteractionDTO::fromInteraction(FieldInteraction::fromArray([
            'trigger' => 'change',
            'action' => 'filter_options',
            'target' => 'city',
            'condition' => [],
            'params' => ['mode' => 'server', 'filter_param' => 'country_id'],
        ]));

        $json = $dto->jsonSerialize();

        self::assertSame('filter_options', $json['action']);
        self::assertSame('city', $json['target']);
        self::assertSame('server', $json['params']['mode']);
    }

    #[Test]
    public function testJsonSerializeAjaxValidate(): void
    {
        $dto = InteractionDTO::fromInteraction(FieldInteraction::fromArray([
            'trigger' => 'blur',
            'action' => 'ajax_validate',
            'target' => null,
            'condition' => [],
            'params' => ['endpoint' => '/api/check-email', 'method' => 'GET', 'debounce' => 400],
        ]));

        $json = $dto->jsonSerialize();

        self::assertSame('blur', $json['trigger']);
        self::assertNull($json['target']);
        self::assertSame('/api/check-email', $json['params']['endpoint']);
    }

    #[Test]
    public function testJsonSerializeCompute(): void
    {
        $dto = InteractionDTO::fromInteraction(FieldInteraction::fromArray([
            'trigger' => 'change',
            'action' => 'compute',
            'target' => null,
            'condition' => [],
            'params' => ['expression' => '{price} * {quantity}', 'sources' => ['price', 'quantity'], 'decimals' => 2],
        ]));

        $json = $dto->jsonSerialize();

        self::assertSame('compute', $json['action']);
        self::assertSame('{price} * {quantity}', $json['params']['expression']);
        self::assertSame(['price', 'quantity'], $json['params']['sources']);
    }
}
