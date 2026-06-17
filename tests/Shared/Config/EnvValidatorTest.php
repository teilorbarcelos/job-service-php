<?php

declare(strict_types=1);

namespace Tests\Shared\Config;

use App\Core\Exceptions\ConfigurationError;
use App\Shared\Config\EnvValidator;
use PHPUnit\Framework\TestCase;

class EnvValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['LOG_LEVEL'] = 'error';
    }

    public function testLoadReturnsAppSettingsWithDefaults(): void
    {
        $settings = EnvValidator::load();

        $this->assertSame('testing', $settings->appEnv);
        $this->assertFalse($settings->appDebug);
        $this->assertSame('error', $settings->logLevel);
        $this->assertSame(30000, $settings->shutdownTimeoutMs);
        $this->assertSame(300000, $settings->jobExecutionTimeoutMs);
        $this->assertSame('sqlite', $settings->dbDriver);
        $this->assertSame(':memory:', $settings->dbDatabase);
        $this->assertSame('localhost', $settings->redisHost);
        $this->assertSame(6379, $settings->redisPort);
        $this->assertFalse($settings->messagingEnabled);
        $this->assertFalse($settings->healthCheckEnabled);
        $this->assertSame('*/1 * * * *', $settings->healthCheckCron);
    }

    public function testLoadWithOverrides(): void
    {
        $_ENV['DB_DRIVER'] = 'pgsql';
        $_ENV['DB_HOST'] = 'pg.example.com';
        $_ENV['DB_PORT'] = '5432';
        $_ENV['DB_DATABASE'] = 'mydb';
        $_ENV['DB_USERNAME'] = 'user';
        $_ENV['DB_PASSWORD'] = 'pass';
        $_ENV['REDIS_HOST'] = 'redis.example.com';
        $_ENV['REDIS_PORT'] = '6380';
        $_ENV['MESSAGING_ENABLED'] = 'true';
        $_ENV['HEALTH_CHECK_ENABLED'] = 'false';
        $_ENV['HEALTH_CHECK_CRON'] = '0 3 * * *';
        $_ENV['APP_DEBUG'] = 'true';

        $settings = EnvValidator::load();

        $this->assertSame('pgsql', $settings->dbDriver);
        $this->assertSame('pg.example.com', $settings->dbHost);
        $this->assertSame(5432, $settings->dbPort);
        $this->assertSame('mydb', $settings->dbDatabase);
        $this->assertSame('user', $settings->dbUsername);
        $this->assertSame('pass', $settings->dbPassword);
        $this->assertSame('redis.example.com', $settings->redisHost);
        $this->assertSame(6380, $settings->redisPort);
        $this->assertTrue($settings->messagingEnabled);
        $this->assertFalse($settings->healthCheckEnabled);
        $this->assertSame('0 3 * * *', $settings->healthCheckCron);
        $this->assertTrue($settings->appDebug);
    }

    public function testLoadWithInvalidIntThrows(): void
    {
        $_ENV['DB_PORT'] = 'not-a-number';

        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('Invalid integer value for DB_PORT');
        EnvValidator::load();
    }

    public function testLoadWithYesBool(): void
    {
        $_ENV['MESSAGING_ENABLED'] = 'yes';
        $settings = EnvValidator::load();
        $this->assertTrue($settings->messagingEnabled);
    }
}
