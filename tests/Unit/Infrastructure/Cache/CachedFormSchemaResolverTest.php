<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Tests\Unit\Infrastructure\Cache;

use Letkode\FormSchema\Application\DTO\FormDTO;
use Letkode\FormSchema\Domain\Contract\FormSchemaResolverInterface;
use Letkode\FormSchema\Infrastructure\Cache\CachedFormSchemaResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class CachedFormSchemaResolverTest extends TestCase
{
    private FormSchemaResolverInterface&MockObject $inner;
    private CacheItemPoolInterface&MockObject $cache;
    private CachedFormSchemaResolver $resolver;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(FormSchemaResolverInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);

        $this->inner->method('schema')->willReturnSelf();
        $this->inner->method('withLocale')->willReturnSelf();
        $this->inner->method('withContext')->willReturnSelf();
        $this->inner->method('includingSections')->willReturnSelf();
        $this->inner->method('excludingSections')->willReturnSelf();
        $this->inner->method('includingGroups')->willReturnSelf();
        $this->inner->method('excludingGroups')->willReturnSelf();

        $this->resolver = new CachedFormSchemaResolver(
            inner: $this->inner,
            cache: $this->cache,
            ttl: 3600,
            prefix: 'fsb',
        );
    }

    private function makeFormDTO(): FormDTO
    {
        return new FormDTO(
            id: 'uuid-1',
            name: 'Test Form',
            tag: 'test_form',
            locale: 'es',
            defaultLocale: 'es',
            enabled: true,
            parameters: [],
            renderType: 'simple',
            renderMeta: [],
            translations: null,
            sections: [],
        );
    }

    #[Test]
    public function testReturnsCachedDtoOnHit(): void
    {
        $dto = $this->makeFormDTO();

        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn($dto);

        $this->cache->method('getItem')->willReturn($item);

        $this->inner->expects(self::never())->method('resolve');

        $resolved = $this->resolver->schema('test_form')->resolve();

        self::assertSame($dto, $resolved);
    }

    #[Test]
    public function testCallsInnerOnMiss(): void
    {
        $dto = $this->makeFormDTO();

        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);
        $item->method('set')->willReturnSelf();
        $item->method('expiresAfter')->willReturnSelf();

        $this->cache->method('getItem')->willReturn($item);
        $this->cache->expects(self::once())->method('save')->with($item);

        $this->inner->expects(self::once())->method('resolve')->willReturn($dto);

        $resolved = $this->resolver->schema('test_form')->resolve();

        self::assertSame($dto, $resolved);
    }

    #[Test]
    public function testBuildKeyIsDeterministic(): void
    {
        $keys = [];

        $this->cache->method('getItem')->willReturnCallback(function (string $key) use (&$keys): CacheItemInterface {
            $keys[] = $key;
            $item = $this->createMock(CacheItemInterface::class);
            $item->method('isHit')->willReturn(false);
            $item->method('set')->willReturnSelf();
            $item->method('expiresAfter')->willReturnSelf();

            return $item;
        });
        $this->cache->method('save');
        $this->inner->method('resolve')->willReturn($this->makeFormDTO());

        $this->resolver->schema('my_form')->withLocale('es')->resolve();
        $this->resolver->schema('my_form')->withLocale('es')->resolve();

        self::assertCount(2, $keys);
        self::assertSame($keys[0], $keys[1]);
    }

    #[Test]
    public function testBuildKeyDiffersForDifferentLocale(): void
    {
        $keys = [];

        $this->cache->method('getItem')->willReturnCallback(function (string $key) use (&$keys): CacheItemInterface {
            $keys[] = $key;
            $item = $this->createMock(CacheItemInterface::class);
            $item->method('isHit')->willReturn(false);
            $item->method('set')->willReturnSelf();
            $item->method('expiresAfter')->willReturnSelf();

            return $item;
        });
        $this->cache->method('save');
        $this->inner->method('resolve')->willReturn($this->makeFormDTO());

        $this->resolver->schema('my_form')->withLocale('es')->resolve();
        $this->resolver->schema('my_form')->withLocale('en')->resolve();

        self::assertCount(2, $keys);
        self::assertNotSame($keys[0], $keys[1]);
    }

    #[Test]
    public function testBuildKeyIsSameRegardlessOfFilterOrder(): void
    {
        $keys = [];

        $this->cache->method('getItem')->willReturnCallback(function (string $key) use (&$keys): CacheItemInterface {
            $keys[] = $key;
            $item = $this->createMock(CacheItemInterface::class);
            $item->method('isHit')->willReturn(false);
            $item->method('set')->willReturnSelf();
            $item->method('expiresAfter')->willReturnSelf();

            return $item;
        });
        $this->cache->method('save');
        $this->inner->method('resolve')->willReturn($this->makeFormDTO());

        $this->resolver->schema('my_form')->includingSections(['b', 'a'])->resolve();
        $this->resolver->schema('my_form')->includingSections(['a', 'b'])->resolve();

        self::assertCount(2, $keys);
        self::assertSame($keys[0], $keys[1]);
    }
}
