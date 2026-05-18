<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\Cache;

use Letkode\FormSchema\Infrastructure\Cache\FormSchemaCacheInvalidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

final class FormSchemaCacheInvalidatorTest extends TestCase
{
    #[Test]
    public function testInvalidateUsesTagsWhenTagAwareAdapter(): void
    {
        /** @var TagAwareAdapterInterface&CacheItemPoolInterface&MockObject $cache */
        $cache = $this->createMock(TagAwareAdapterInterface::class);
        $cache->expects(self::once())
            ->method('invalidateTags')
            ->with(['fsb.my_form']);
        $cache->expects(self::never())->method('clear');

        $invalidator = new FormSchemaCacheInvalidator($cache, 'fsb');
        $invalidator->invalidate('my_form');
    }

    #[Test]
    public function testInvalidateClearsAllWhenNotTagAware(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects(self::once())->method('clear');

        $invalidator = new FormSchemaCacheInvalidator($cache, 'fsb');
        $invalidator->invalidate('my_form');
    }

    #[Test]
    public function testInvalidateAll(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects(self::once())->method('clear');

        $invalidator = new FormSchemaCacheInvalidator($cache, 'fsb');
        $invalidator->invalidateAll();
    }
}
