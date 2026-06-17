<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

class RedisProvider
{
    private static ?self $instance = null;
    private ?\Redis $redis = null;

    final public function __construct(?\Redis $redis = null)
    {
        if ($redis !== null) {
            $this->redis = $redis;
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getNativeRedis(): \Redis
    {
        if ($this->redis === null) {
            $this->redis = new \Redis();
            $host = $_ENV['REDIS_HOST'] ?? 'localhost';
            $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
            $this->redis->connect($host, $port, 2.5);
        }
        return $this->redis;
    }

    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            try {
                if (self::$instance->redis !== null) {
                    self::$instance->redis->close();
                }
            } catch (\Throwable) {
            }
        }
        self::$instance = null;
    }

    public function hasRedis(): bool
    {
        return $this->redis !== null;
    }
}
