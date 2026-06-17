<?php

declare(strict_types=1);

namespace App\Infrastructure\Health;

use App\Infrastructure\Database\DatabaseProvider;
use App\Infrastructure\Redis\RedisProvider;
use App\Infrastructure\Messaging\RabbitMQProvider;

class DefaultHealthChecker implements HealthCheckerInterface
{
    public function __construct(
        private DatabaseProvider $database,
        private RedisProvider $redis,
        private RabbitMQProvider $rabbitmq,
    ) {}

    public function checkPostgres(): HealthCheckResult
    {
        $start = hrtime(true);
        try {
            $ok = $this->database->ping();
            $latency = (int)((hrtime(true) - $start) / 1_000_000);
            if ($ok) {
                return new HealthCheckResult('up', $latency);
            }
            return new HealthCheckResult('down', $latency, 'Database ping returned false');
        } catch (\Throwable $e) {
            $latency = (int)((hrtime(true) - $start) / 1_000_000);
            return new HealthCheckResult('down', $latency, $e->getMessage());
        }
    }

    public function checkRedis(): HealthCheckResult
    {
        $start = hrtime(true);
        try {
            $native = $this->redis->getNativeRedis();
            $native->ping();
            $latency = (int)((hrtime(true) - $start) / 1_000_000);
            return new HealthCheckResult('up', $latency);
        } catch (\Throwable $e) {
            $latency = (int)((hrtime(true) - $start) / 1_000_000);
            return new HealthCheckResult('down', $latency, $e->getMessage());
        }
    }

    public function checkRabbitMQ(): HealthCheckResult
    {
        $start = hrtime(true);
        try {
            if ($this->rabbitmq->isOpen()) {
                $latency = (int)((hrtime(true) - $start) / 1_000_000);
                return new HealthCheckResult('up', $latency);
            }
            if (($_ENV['MESSAGING_ENABLED'] ?? 'false') !== 'true') {
                return new HealthCheckResult('disabled');
            }
            $latency = (int)((hrtime(true) - $start) / 1_000_000);
            return new HealthCheckResult('down', $latency, 'Not connected');
        } catch (\Throwable $e) {
            $latency = (int)((hrtime(true) - $start) / 1_000_000);
            return new HealthCheckResult('down', $latency, $e->getMessage());
        }
    }
}
