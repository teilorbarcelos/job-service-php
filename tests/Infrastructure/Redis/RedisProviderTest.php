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

    public function testSingleton(): void
    {
        $instance1 = RedisProvider::getInstance();
        $instance2 = RedisProvider::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    public function testResetInstance(): void
    {
        $instance1 = RedisProvider::getInstance();
        RedisProvider::resetInstance();
        $instance2 = RedisProvider::getInstance();
        $this->assertNotSame($instance1, $instance2);
    }

    public function testGetNativeRedisThrowsWhenNoRedis(): void
    {
        $provider = RedisProvider::getInstance();
        $this->expectException(\Exception::class);
        $provider->getNativeRedis();
    }
}
