<?php

declare(strict_types=1);

namespace Tests\Shared\Config;

use App\Shared\Config\AppSettings;
use PHPUnit\Framework\TestCase;

class AppSettingsTest extends TestCase
{
    public function testConstructor(): void
    {
        $settings = new AppSettings(
            appEnv: 'production',
            appDebug: false,
            logLevel: 'info',
            shutdownTimeoutMs: 60000,
            jobExecutionTimeoutMs: 180000,
            dbDriver: 'pgsql',
            dbHost: 'db.example.com',
            dbPort: 5432,
            dbDatabase: 'mydb',
            dbUsername: 'user',
            dbPassword: 'secret',
            redisHost: 'redis.example.com',
            redisPort: 6379,
            messagingEnabled: true,
            rabbitHost: 'rabbit.example.com',
            rabbitPort: 5672,
            rabbitUser: 'guest',
            rabbitPassword: 'guest',
            healthCheckCron: '0 3 * * *',
            healthCheckEnabled: true,
        );

        $this->assertSame('production', $settings->appEnv);
        $this->assertFalse($settings->appDebug);
        $this->assertSame('info', $settings->logLevel);
        $this->assertSame(60000, $settings->shutdownTimeoutMs);
        $this->assertSame(180000, $settings->jobExecutionTimeoutMs);
        $this->assertSame('pgsql', $settings->dbDriver);
        $this->assertSame('db.example.com', $settings->dbHost);
        $this->assertSame(5432, $settings->dbPort);
        $this->assertSame('mydb', $settings->dbDatabase);
        $this->assertTrue($settings->messagingEnabled);
        $this->assertTrue($settings->healthCheckEnabled);
    }
}
