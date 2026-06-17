<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\CronAdapter;
use App\Core\Scheduler;
use App\Infrastructure\Health\DefaultHealthChecker;
use App\Shared\Config\AppSettings;
use Psr\Log\LoggerInterface;

// [GENERATOR_IMPORTS]

function registerJobs(AppSettings $settings, CronAdapter $cron, LoggerInterface $logger, DefaultHealthChecker $checker): Scheduler
{
    $healthCheckJob = new HealthCheckJob($checker);
    $healthCheckJob->enabled = $settings->healthCheckEnabled;
    $healthCheckJob->schedule = $settings->healthCheckCron;

    $jobs = [
        $healthCheckJob,
        // [GENERATOR_JOBS]
    ];

    return new Scheduler($jobs, $cron, $logger, $settings->jobExecutionTimeoutMs);
}
