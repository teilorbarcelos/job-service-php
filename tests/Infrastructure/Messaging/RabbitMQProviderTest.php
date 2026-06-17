<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging;

use App\Infrastructure\Messaging\RabbitMQProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RabbitMQProviderTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $_ENV['MESSAGING_ENABLED'] = 'false';
    }

    public function testIsOpenReturnsFalseWhenDisabled(): void
    {
        $provider = new RabbitMQProvider($this->logger);
        $this->assertFalse($provider->isOpen());
    }

    public function testConnectDoesNothingWhenDisabled(): void
    {
        $provider = new RabbitMQProvider($this->logger);
        $provider->connect();
        $this->assertFalse($provider->isOpen());
    }

    public function testPublishDoesNothingWhenDisabled(): void
    {
        $provider = new RabbitMQProvider($this->logger);
        $provider->publish('test', ['key' => 'value']);
        $this->assertFalse($provider->isOpen());
    }

    public function testCloseWithNoConnectionDoesNothing(): void
    {
        $provider = new RabbitMQProvider($this->logger);
        $provider->close();
        $this->assertFalse($provider->isOpen());
    }

    public function testConnectWithFailingFactoryThrows(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';
        $factory = function (): void {
            throw new \RuntimeException('connection factory failed');
        };

        $provider = new RabbitMQProvider($this->logger, $factory);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('connection factory failed');
        $provider->connect();
    }

    public function testPublishWithNoChannelDoesNotThrow(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';
        $factory = function (): void {
            throw new \RuntimeException('connection factory failed');
        };

        $provider = new RabbitMQProvider($this->logger, $factory);
        $provider->publish('test', ['key' => 'value']);
        $this->assertFalse($provider->isOpen());
    }

    public function testIsOpenReturnsFalseAfterFailedConnect(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';
        $factory = function (): void {
            throw new \RuntimeException('connection factory failed');
        };

        $provider = new RabbitMQProvider($this->logger, $factory);

        try {
            $provider->connect();
        } catch (\RuntimeException) {
        }

        $this->assertFalse($provider->isOpen());
    }

    public function testIsOpenWithGoodFactoryReturnsTrue(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';

        $channel = $this->createMock(\PhpAmqpLib\Channel\AMQPChannel::class);

        $connection = $this->createMock(\PhpAmqpLib\Connection\AMQPStreamConnection::class);
        $connection->method('isConnected')->willReturn(true);
        $connection->method('channel')->willReturn($channel);

        $factory = function () use ($connection): \PhpAmqpLib\Connection\AMQPStreamConnection {
            return $connection;
        };

        $provider = new RabbitMQProvider($this->logger, $factory);
        $provider->connect();

        $this->assertTrue($provider->isOpen());
    }

    public function testCloseWithOpenConnection(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';

        $channel = $this->createMock(\PhpAmqpLib\Channel\AMQPChannel::class);
        $connection = $this->createMock(\PhpAmqpLib\Connection\AMQPStreamConnection::class);
        $connection->method('isConnected')->willReturn(true);
        $connection->method('channel')->willReturn($channel);

        $factory = function () use ($connection): \PhpAmqpLib\Connection\AMQPStreamConnection {
            return $connection;
        };

        $provider = new RabbitMQProvider($this->logger, $factory);
        $provider->connect();
        $provider->close();

        $this->assertFalse($provider->isOpen());
    }

    public function testConnectWhenAlreadyConnectedDoesNothing(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';

        $channel = $this->createMock(\PhpAmqpLib\Channel\AMQPChannel::class);
        $connection = $this->createMock(\PhpAmqpLib\Connection\AMQPStreamConnection::class);
        $connection->method('isConnected')->willReturn(true);
        $connection->method('channel')->willReturn($channel);

        $factory = function () use ($connection): \PhpAmqpLib\Connection\AMQPStreamConnection {
            return $connection;
        };

        $provider = new RabbitMQProvider($this->logger, $factory);
        $provider->connect(); // first time
        $provider->connect(); // second time (should no-op)

        $this->assertTrue($provider->isOpen());
    }
}
