<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Health;

use App\Infrastructure\Database\DatabaseProvider;
use App\Infrastructure\Health\DefaultHealthChecker;
use App\Infrastructure\Messaging\RabbitMQProvider;
use App\Infrastructure\Redis\RedisProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FailingDatabaseProvider extends DatabaseProvider
{
    public function __construct()
    {
    }

    public function ping(): bool
    {
        throw new \RuntimeException('Database connection failed');
    }
}

class FalsePingDatabaseProvider extends DatabaseProvider
{
    public function __construct()
    {
    }

    public function ping(): bool
    {
        return false;
    }
}

class DefaultHealthCheckerTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        DatabaseProvider::resetInstance();
        RedisProvider::resetInstance();
        $_ENV['MESSAGING_ENABLED'] = 'false';
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testCheckPostgresReturnsUpWithSqlite(): void
    {
        $database = DatabaseProvider::getInstance();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $result = $checker->checkPostgres();
        $this->assertSame('up', $result->status);
        $this->assertNotNull($result->latencyMs);
    }

    public function testCheckPostgresReturnsDownOnFalse(): void
    {
        $database = new FalsePingDatabaseProvider();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $result = $checker->checkPostgres();
        $this->assertSame('down', $result->status);
        $this->assertStringContainsString('Database ping returned false', $result->error ?? '');
    }

    public function testCheckPostgresReturnsDownOnCatch(): void
    {
        $database = new FailingDatabaseProvider();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $result = $checker->checkPostgres();
        $this->assertSame('down', $result->status);
        $this->assertStringContainsString('Database connection failed', $result->error ?? '');
    }

    public function testCheckRedisReturnsDown(): void
    {
        $database = DatabaseProvider::getInstance();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $result = $checker->checkRedis();
        $this->assertSame('down', $result->status);
        $this->assertNotNull($result->error);
    }

    public function testCheckRedisWithMockReturnsUp(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->method('ping')->willReturn(true);

        $database = DatabaseProvider::getInstance();
        $redis = new RedisProvider($redisMock);
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $result = $checker->checkRedis();
        $this->assertSame('up', $result->status);
    }

    public function testCheckRabbitMQReturnsDisabled(): void
    {
        $database = DatabaseProvider::getInstance();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $result = $checker->checkRabbitMQ();
        $this->assertSame('disabled', $result->status);
    }

    public function testCheckRabbitMQReturnsDownWhenEnabledButNotConnected(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';
        $database = DatabaseProvider::getInstance();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $result = $checker->checkRabbitMQ();
        $this->assertSame('down', $result->status);
        $this->assertNotNull($result->error);
    }

    public function testCheckRabbitMQWithMockReturnsUp(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';

        $channel = $this->createMock(\PhpAmqpLib\Channel\AMQPChannel::class);
        $connection = $this->createMock(\PhpAmqpLib\Connection\AMQPStreamConnection::class);
        $connection->method('isConnected')->willReturn(true);
        $connection->method('channel')->willReturn($channel);

        $factory = function () use ($connection): \PhpAmqpLib\Connection\AMQPStreamConnection {
            return $connection;
        };

        $database = DatabaseProvider::getInstance();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger, $factory);
        $rabbitmq->connect();

        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);
        $result = $checker->checkRabbitMQ();
        $this->assertSame('up', $result->status);
    }

    public function testCheckAllServicesReportIndependently(): void
    {
        $database = DatabaseProvider::getInstance();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $this->assertNotNull($checker->checkPostgres()->status);
        $this->assertNotNull($checker->checkRedis()->status);
        $this->assertSame('disabled', $checker->checkRabbitMQ()->status);
    }
}
