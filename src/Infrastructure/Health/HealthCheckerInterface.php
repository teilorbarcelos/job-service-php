<?php

declare(strict_types=1);

namespace App\Infrastructure\Health;

interface HealthCheckerInterface
{
    public function checkPostgres(): HealthCheckResult;
    public function checkRedis(): HealthCheckResult;
    public function checkRabbitMQ(): HealthCheckResult;
}
