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
    }

    public function testIsOpenReturnsFalseWhenDisabled(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'false';
        $provider = new RabbitMQProvider($this->logger);
        $this->assertFalse($provider->isOpen());
    }

    public function testConnectDoesNothingWhenDisabled(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'false';
        $provider = new RabbitMQProvider($this->logger);
        $provider->connect();
        $this->assertFalse($provider->isOpen());
    }

    public function testPublishDoesNothingWhenDisabled(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'false';
        $provider = new RabbitMQProvider($this->logger);
        $provider->publish('test', ['key' => 'value']);
        $this->assertFalse($provider->isOpen());
    }

    public function testCloseWithNoConnectionDoesNothing(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';
        $provider = new RabbitMQProvider($this->logger);
        $provider->close();
        $this->assertFalse($provider->isOpen());
    }

    public function testConnectWithBadHostThrows(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'true';
        $_ENV['RABBIT_HOST'] = '192.0.2.1'; // non-routable
        $_ENV['RABBIT_PORT'] = '5672';

        $provider = new RabbitMQProvider($this->logger);

        $this->expectException(\Exception::class);
        $provider->connect();
    }

    public function testConnectWithBadHostViaFactoryThrows(): void
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
}
