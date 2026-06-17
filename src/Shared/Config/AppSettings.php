<?php

declare(strict_types=1);

namespace App\Shared\Config;

use App\Core\Exceptions\ConfigurationError;

readonly class AppSettings
{
    public function __construct(
        public string $appEnv,
        public bool $appDebug,
        public string $logLevel,
        public int $shutdownTimeoutMs,
        public int $jobExecutionTimeoutMs,
        public string $dbDriver,
        public string $dbHost,
        public int $dbPort,
        public string $dbDatabase,
        public string $dbUsername,
        public string $dbPassword,
        public string $redisHost,
        public int $redisPort,
        public bool $messagingEnabled,
        public string $rabbitHost,
        public int $rabbitPort,
        public string $rabbitUser,
        public string $rabbitPassword,
        public string $healthCheckCron,
        public bool $healthCheckEnabled,
    ) {}
}
