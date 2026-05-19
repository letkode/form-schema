<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\OptionsSource;

use Doctrine\ORM\EntityManagerInterface;
use Letkode\FormSchema\Application\DTO\OptionDTO;
use Letkode\FormSchema\Attribute\AsOptionsSource;
use Letkode\FormSchema\Domain\Contract\OptionsSourceInterface;
use Letkode\FormSchema\Domain\Exception\UnauthorizedOptionsMethodException;

#[AsOptionsSource]
final class OptionsEntitySource implements OptionsSourceInterface
{
    /** @var array<string, list<string>> */
    private array $allowedMethods = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[\Override]
    public static function getName(): string
    {
        return 'entity';
    }

    #[\Override]
    public function resolve(array $parameters, string|null $locale = null): array
    {
        $entityClass = $parameters['entity'] ?? null;
        $method = $parameters['method'] ?? 'findForFormOptions';

        if (null === $entityClass) {
            return [];
        }

        $this->guardMethodAllowed($entityClass, $method);

        $repository = $this->entityManager->getRepository($entityClass);
        $context = $parameters['context'] ?? [];

        $rawOptions = $repository->{$method}($context);

        $idColumn = $parameters['id_column'] ?? 'id';
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

        foreach ($rawOptions as $position => $item) {
            if (\is_array($item)) {
                $options[] = new OptionDTO(
                    value: $item[$idColumn] ?? $item['id'] ?? '',
                    text: $item[$textColumn] ?? $item['text'] ?? '',
                    position: $item['position'] ?? $position,
                    data: array_diff_key($item, array_flip([$idColumn, $textColumn, 'position'])),
                );
            } elseif (\is_object($item)) {
                $options[] = new OptionDTO(
                    value: $item->{$idColumn} ?? $item->id ?? '',
                    text: $item->{$textColumn} ?? $item->text ?? '',
                    position: $item->position ?? $position,
                );
            }
        }

        return $options;
    }

    /** @param list<string> $methods */
    public function registerProvider(string $class, array $methods): void
    {
        foreach ($methods as $method) {
            if (!\in_array($method, $this->allowedMethods[$class] ?? [], true)) {
                $this->allowedMethods[$class][] = $method;
            }
        }
    }

    private function guardMethodAllowed(string $entityClass, string $method): void
    {
        $allowedForClass = $this->allowedMethods[$entityClass] ?? null;

        if (null === $allowedForClass || !\in_array($method, $allowedForClass, true)) {
            throw new UnauthorizedOptionsMethodException($entityClass, $method);
        }
    }
}
