<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\OptionsSource;

use Letkode\FormSchema\Application\DTO\OptionDTO;
use Letkode\FormSchema\Attribute\AsOptionsSource;
use Letkode\FormSchema\Domain\Contract\OptionsSourceInterface;
use Letkode\FormSchema\Domain\Entity\FormOptionGeneralValue;
use Letkode\FormSchema\Domain\Repository\FormOptionGeneralRepositoryInterface;

#[AsOptionsSource]
final class OptionsGeneralSource implements OptionsSourceInterface
{
    public function __construct(
        private readonly FormOptionGeneralRepositoryInterface $repository,
    ) {
    }

    #[\Override]
    public static function getName(): string
    {
        return 'general';
    }

    #[\Override]
    public function resolve(array $parameters, string|null $locale = null): array
    {
        $tag = $parameters['set'] ?? null;
        if (null === $tag) {
            return [];
        }

        $optionGroup = $this->repository->findOneByTag($tag);
        if (null === $optionGroup) {
            return [];
        }

        $idColumn = $parameters['id_column'] ?? 'tag';
        $textColumn = $parameters['text_column'] ?? 'text';
        $withAllOption = $parameters['with_all_option'] ?? false;
        $allOptionParams = $parameters['all_option_params'] ?? [];

        $options = [];

        if ($withAllOption) {
            $options[] = new OptionDTO(
                value: $allOptionParams['value'] ?? '',
                text: $allOptionParams['text'] ?? 'Todos',
                position: -1,
            );
        }

        /** @var FormOptionGeneralValue $value */
        foreach ($optionGroup->values as $value) {
            if (!$value->enabled) {
                continue;
            }

            $resolvedText = (null !== $locale ? $value->getTranslation($locale, $textColumn) : null)
                ?? $value->text;

            $rawValue = match ($idColumn) {
                'tag' => $value->tag,
                'id' => (string) $value->id,
                default => $value->tag,
            };

            $options[] = new OptionDTO(
                value: $rawValue,
                text: $resolvedText,
                tag: $value->tag,
                icon: $value->getParameter('icon'),
                color: $value->getParameter('color'),
                position: $value->position,
                data: array_diff_key($value->parameters, array_flip(['icon', 'color'])),
            );
        }

        return $options;
    }
}
