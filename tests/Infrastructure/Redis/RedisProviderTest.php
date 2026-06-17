<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Redis;

use App\Infrastructure\Redis\RedisProvider;
use PHPUnit\Framework\TestCase;

class RedisProviderTest extends TestCase
{
    protected function setUp(): void
    {
        RedisProvider::resetInstance();
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $instance1 = RedisProvider::getInstance();
        $instance2 = RedisProvider::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    public function testResetInstanceCreatesNewSingleton(): void
    {
        $instance1 = RedisProvider::getInstance();
        RedisProvider::resetInstance();
        $instance2 = RedisProvider::getInstance();
        $this->assertNotSame($instance1, $instance2);
    }

    public function testGetNativeRedisThrowsWhenNoRedisAvailable(): void
    {
        $provider = RedisProvider::getInstance();
        $this->expectException(\Exception::class);
        $provider->getNativeRedis();
    }

    public function testResetInstanceWithNoRedisDoesNotThrow(): void
    {
        RedisProvider::getInstance();
        RedisProvider::resetInstance();
        $this->assertTrue(true);
    }

    public function testConstructorWithMockRedis(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $provider = new RedisProvider($redisMock);

        $native = $provider->getNativeRedis();
        $this->assertSame($redisMock, $native);
        $this->assertTrue($provider->hasRedis());
    }

    public function testHasRedisReturnsFalseByDefault(): void
    {
        $provider = new RedisProvider();
        $this->assertFalse($provider->hasRedis());
    }

    public function testHasRedisReturnsTrueWithMock(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $provider = new RedisProvider($redisMock);
        $this->assertTrue($provider->hasRedis());
    }

    public function testGetNativeRedisWithMockDoesNotConnect(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $provider = new RedisProvider($redisMock);

        $result = $provider->getNativeRedis();
        $this->assertSame($redisMock, $result);
    }

    public function testResetWithMockRedisDoesNotThrow(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->method('close')->willReturn(true);

        $provider = new RedisProvider($redisMock);
        RedisProvider::resetInstance();

        $this->assertTrue(true);
    }
}
