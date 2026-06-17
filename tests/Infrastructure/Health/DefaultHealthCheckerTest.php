<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Health;

use App\Infrastructure\Database\DatabaseProvider;
use App\Infrastructure\Health\DefaultHealthChecker;
use App\Infrastructure\Messaging\RabbitMQProvider;
use App\Infrastructure\Redis\RedisProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DefaultHealthCheckerTest extends TestCase
{
    private LoggerInterface $logger;
    private bool $sqliteAvailable;

    protected function setUp(): void
    {
        DatabaseProvider::resetInstance();
        RedisProvider::resetInstance();
        $_ENV['MESSAGING_ENABLED'] = 'false';
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->sqliteAvailable = in_array('sqlite', \PDO::getAvailableDrivers());
    }

    public function testCheckPostgresReturnsUpOrDown(): void
    {
        $database = DatabaseProvider::getInstance();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $result = $checker->checkPostgres();
        if ($this->sqliteAvailable) {
            $this->assertSame('up', $result->status);
        } else {
            $this->assertSame('down', $result->status);
        }
        $this->assertNotNull($result->latencyMs);
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

    public function testCheckRabbitMQReturnsDisabled(): void
    {
        $database = DatabaseProvider::getInstance();
        $redis = RedisProvider::getInstance();
        $rabbitmq = new RabbitMQProvider($this->logger);
        $checker = new DefaultHealthChecker($database, $redis, $rabbitmq);

        $result = $checker->checkRabbitMQ();
        $this->assertSame('disabled', $result->status);
    }

    public function testCheckRabbitMQReturnsDownWhenEnabled(): void
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
}
