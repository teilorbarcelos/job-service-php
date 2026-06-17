<?php

declare(strict_types=1);

namespace App\Shared\Config;

use App\Core\Exceptions\ConfigurationError;

final class EnvValidator
{
    public static function load(): AppSettings
    {
        $getenv = function (string $key, string $default = ''): string {
            return $_ENV[$key] ?? (getenv($key) ?: $default);
        };

        $getint = function (string $key, int $default = 0): int {
            $value = $_ENV[$key] ?? (getenv($key) ?: '');
            if ($value === '') {
                return $default;
            }
            if (!is_numeric($value)) {
                throw new ConfigurationError("Invalid integer value for {$key}: {$value}");
            }
            return (int)$value;
        };

        $getbool = function (string $key, bool $default = false): bool {
            $value = $_ENV[$key] ?? (getenv($key) ?: '');
            if ($value === '') {
                return $default;
            }
            return in_array(strtolower($value), ['true', '1', 'yes'], true);
        };

        return new AppSettings(
            appEnv: $getenv('APP_ENV', 'development'),
            appDebug: $getbool('APP_DEBUG', false),
            logLevel: $getenv('LOG_LEVEL', 'info'),
            shutdownTimeoutMs: $getint('SHUTDOWN_TIMEOUT_MS', 30000),
            jobExecutionTimeoutMs: $getint('JOB_EXECUTION_TIMEOUT_MS', 300000),
            dbDriver: $getenv('DB_DRIVER', 'sqlite'),
            dbHost: $getenv('DB_HOST', 'localhost'),
            dbPort: $getint('DB_PORT', 5432),
            dbDatabase: $getenv('DB_DATABASE', ':memory:'),
            dbUsername: $getenv('DB_USERNAME', 'postgres'),
            dbPassword: $getenv('DB_PASSWORD', 'postgrespw'),
            redisHost: $getenv('REDIS_HOST', 'localhost'),
            redisPort: $getint('REDIS_PORT', 6379),
            messagingEnabled: $getbool('MESSAGING_ENABLED', false),
            rabbitHost: $getenv('RABBIT_HOST', 'localhost'),
            rabbitPort: $getint('RABBIT_PORT', 5672),
            rabbitUser: $getenv('RABBIT_USER', 'guest'),
            rabbitPassword: $getenv('RABBIT_PASSWORD', 'guest'),
            healthCheckCron: $getenv('HEALTH_CHECK_CRON', '*/1 * * * *'),
            healthCheckEnabled: $getbool('HEALTH_CHECK_ENABLED', true),
        );
    }
}
