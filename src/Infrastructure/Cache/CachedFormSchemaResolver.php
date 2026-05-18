<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Cache;

use Letkode\FormSchema\Application\DTO\FormDTO;
use Letkode\FormSchema\Domain\Contract\FormSchemaResolverInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

final class CachedFormSchemaResolver implements FormSchemaResolverInterface
{
    private string|null $tag = null;
    private string|null $locale = null;
    private string|null $context = null;
    private array $includeSections = [];
    private array $excludeSections = [];
    private array $includeGroups = [];
    private array $excludeGroups = [];

    public function __construct(
        private FormSchemaResolverInterface $inner,
        private readonly CacheItemPoolInterface $cache,
        private readonly int $ttl,
        private readonly string $prefix,
    ) {
    }

    #[\Override]
    public function schema(string $tag): static
    {
        $clone = clone $this;
        $clone->tag = $tag;
        $clone->inner = $this->inner->schema($tag);

        return $clone;
    }

    #[\Override]
    public function withLocale(string $locale): static
    {
        $clone = clone $this;
        $clone->locale = $locale;
        $clone->inner = $this->inner->withLocale($locale);

        return $clone;
    }

    #[\Override]
    public function withContext(string $action): static
    {
        $clone = clone $this;
        $clone->context = $action;
        $clone->inner = $this->inner->withContext($action);

        return $clone;
    }

    #[\Override]
    public function includingSections(array $tags): static
    {
        $clone = clone $this;
        $clone->includeSections = $tags;
        $clone->inner = $this->inner->includingSections($tags);

        return $clone;
    }

    #[\Override]
    public function excludingSections(array $tags): static
    {
        $clone = clone $this;
        $clone->excludeSections = $tags;
        $clone->inner = $this->inner->excludingSections($tags);

        return $clone;
    }

    #[\Override]
    public function includingGroups(array $tags): static
    {
        $clone = clone $this;
        $clone->includeGroups = $tags;
        $clone->inner = $this->inner->includingGroups($tags);

        return $clone;
    }

    #[\Override]
    public function excludingGroups(array $tags): static
    {
        $clone = clone $this;
        $clone->excludeGroups = $tags;
        $clone->inner = $this->inner->excludingGroups($tags);

        return $clone;
    }

    #[\Override]
    public function resolve(): FormDTO
    {
        $key = $this->buildKey();
        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        $dto = $this->inner->resolve();

        if ($this->cache instanceof TagAwareAdapterInterface && $item instanceof CacheItem) {
            $item->tag([$this->prefix . '.' . $this->tag]);
        }

        $item->set($dto)->expiresAfter($this->ttl > 0 ? $this->ttl : null);
        $this->cache->save($item);

        return $dto;
    }

    #[\Override]
    public function toArray(): array
    {
        return $this->resolve()->jsonSerialize();
    }

    private function buildKey(): string
    {
        $inclSections = $this->includeSections;
        $exclSections = $this->excludeSections;
        $inclGroups = $this->includeGroups;
        $exclGroups = $this->excludeGroups;

        sort($inclSections);
        sort($exclSections);
        sort($inclGroups);
        sort($exclGroups);

        return $this->prefix . '.' . hash('xxh3', serialize([
            'tag' => $this->tag,
            'locale' => $this->locale,
            'context' => $this->context,
            'incl_sections' => $inclSections,
            'excl_sections' => $exclSections,
            'incl_groups' => $inclGroups,
            'excl_groups' => $exclGroups,
        ]));
    }
}
