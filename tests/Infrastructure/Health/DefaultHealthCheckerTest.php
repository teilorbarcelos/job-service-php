<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Health;

use App\Infrastructure\Database\DatabaseProvider;
use App\Infrastructure\Health\DefaultHealthChecker;
use App\Infrastructure\Health\HealthCheckResult;
use App\Infrastructure\Messaging\RabbitMQProvider;
use App\Infrastructure\Redis\RedisProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DefaultHealthCheckerTest extends TestCase
{
    private DatabaseProvider $database;
    private RedisProvider $redis;
    private RabbitMQProvider $rabbitmq;
    private DefaultHealthChecker $checker;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        DatabaseProvider::resetInstance();
        RedisProvider::resetInstance();
        $_ENV['MESSAGING_ENABLED'] = 'false';

        $this->database = DatabaseProvider::getInstance();
        $this->redis = RedisProvider::getInstance();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->rabbitmq = new RabbitMQProvider($this->logger);
        $this->checker = new DefaultHealthChecker($this->database, $this->redis, $this->rabbitmq);
    }

    public function testCheckPostgresReturnsUp(): void
    {
        $result = $this->checker->checkPostgres();
        $this->assertSame('up', $result->status);
        $this->assertNotNull($result->latencyMs);
    }

    public function testCheckRedisReturnsDown(): void
    {
        // No redis running in test, so it should return down
        $result = $this->checker->checkRedis();
        $this->assertSame('down', $result->status);
        $this->assertNotNull($result->error);
    }

    public function testCheckRabbitMQReturnsDisabled(): void
    {
        $result = $this->checker->checkRabbitMQ();
        $this->assertSame('disabled', $result->status);
    }

    public function testCheckRabbitMQReturnsDownWhenEnabled(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';
        $this->rabbitmq = new RabbitMQProvider($this->logger);
        $this->checker = new DefaultHealthChecker($this->database, $this->redis, $this->rabbitmq);

        $result = $this->checker->checkRabbitMQ();
        $this->assertSame('down', $result->status);
        $this->assertNotNull($result->error);
    }
}
