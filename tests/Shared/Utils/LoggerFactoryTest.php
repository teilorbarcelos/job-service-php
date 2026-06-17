<?php

declare(strict_types=1);

namespace Tests\Shared\Utils;

use App\Shared\Utils\LoggerFactory;
use PHPUnit\Framework\TestCase;

class LoggerFactoryTest extends TestCase
{
    public function testCreateReturnsLogger(): void
    {
        $logger = LoggerFactory::create();
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
    }

    public function testCreateWithCustomLevel(): void
    {
        $logger = LoggerFactory::create('debug');
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
    }

    public function testLoggerLogsInfo(): void
    {
        $logger = LoggerFactory::create('debug');
        $logger->info('test message', ['key' => 'value']);
        $this->assertTrue(true);
    }

    public function testLoggerLogsError(): void
    {
        $logger = LoggerFactory::create('debug');
        $logger->error('error message', ['key' => 'value']);
        $this->assertTrue(true);
    }
}
