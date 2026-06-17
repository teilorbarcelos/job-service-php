<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\BaseJob;
use App\Core\JobContext;
use App\Infrastructure\Health\HealthCheckerInterface;

class HealthCheckJob extends BaseJob
{
    public string $schedule = '*/1 * * * *';

    public function __construct(
        private readonly HealthCheckerInterface $checker,
    ) {}

    public function getName(): string
    {
        return 'health-check';
    }

    public function getSchedule(): string
    {
        return $this->schedule;
    }

    public function getDescription(): string
    {
        return 'Reports connection status with PostgreSQL, Redis and RabbitMQ';
    }

    protected function handle(JobContext $context): void
    {
        $timestamp = date('c');

        $postgres = $this->checker->checkPostgres();
        $redis = $this->checker->checkRedis();
        $rabbitmq = $this->checker->checkRabbitMQ();

        $allUp = $postgres->status === 'up'
            && $redis->status === 'up'
            && ($rabbitmq->status === 'up' || $rabbitmq->status === 'disabled');

        $status = $allUp ? 'healthy' : 'degraded';

        $context->logger->info('Health check completed', [
            'event' => 'health-check',
            'status' => $status,
            'timestamp' => $timestamp,
            'postgres' => ['status' => $postgres->status, 'latency_ms' => $postgres->latencyMs],
            'redis' => ['status' => $redis->status, 'latency_ms' => $redis->latencyMs],
            'rabbitmq' => ['status' => $rabbitmq->status, 'latency_ms' => $rabbitmq->latencyMs],
        ]);

        echo sprintf(
            "[HealthCheck %s] postgres=%s redis=%s rabbitmq=%s\n",
            $timestamp,
            $postgres->status,
            $redis->status,
            $rabbitmq->status
        );
    }
}
