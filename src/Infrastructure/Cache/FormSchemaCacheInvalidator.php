<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Infrastructure\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

final class FormSchemaCacheInvalidator
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly string $prefix,
    ) {
    }

    public function invalidate(string $tag): void
    {
        if ($this->cache instanceof TagAwareAdapterInterface) {
            $this->cache->invalidateTags([$this->prefix . '.' . $tag]);

            return;
        }

        $this->cache->clear();
    }

    public function invalidateAll(): void
    {
        $this->cache->clear();
    }
}
