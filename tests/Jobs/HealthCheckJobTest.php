<?php

declare(strict_types=1);

namespace Tests\Jobs;

use App\Core\JobContext;
use App\Core\JobSignal;
use App\Infrastructure\Database\DatabaseProvider;
use App\Infrastructure\Health\DefaultHealthChecker;
use App\Infrastructure\Health\HealthCheckResult;
use App\Infrastructure\Health\HealthCheckerInterface;
use App\Infrastructure\Redis\RedisProvider;
use App\Infrastructure\Messaging\RabbitMQProvider;
use App\Jobs\HealthCheckJob;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HealthCheckJobTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testGetName(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $job = new HealthCheckJob($checker);
        $this->assertSame('health-check', $job->getName());
    }

    public function testGetSchedule(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $job = new HealthCheckJob($checker);
        $job->schedule = '0 3 * * *';
        $this->assertSame('0 3 * * *', $job->getSchedule());
    }

    public function testGetDescription(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $job = new HealthCheckJob($checker);
        $this->assertStringContainsString('PostgreSQL', $job->getDescription());
        $this->assertStringContainsString('Redis', $job->getDescription());
        $this->assertStringContainsString('RabbitMQ', $job->getDescription());
    }

    public function testHandleLogsHealthyWhenAllUp(): void
    {
        $checker = $this->createAllUpChecker();
        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Health check completed',
                $this->callback(function (array $payload) {
                    return $payload['status'] === 'healthy';
                })
            );

        $job->run(new JobContext($this->logger, new JobSignal()));
    }

    public function testHandleLogsDegradedWhenPostgresDown(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('down', null, 'refused'));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Health check completed',
                $this->callback(function (array $payload) {
                    return $payload['status'] === 'degraded';
                })
            );

        $job->run(new JobContext($this->logger, new JobSignal()));
    }

    public function testHandleLogsDegradedWhenRedisDown(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('down', null, 'timeout'));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Health check completed',
                $this->callback(function (array $payload) {
                    return $payload['status'] === 'degraded';
                })
            );

        $job->run(new JobContext($this->logger, new JobSignal()));
    }

    public function testHandleLogsDegradedWhenRabbitMQDown(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('down', null, 'not connected'));

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Health check completed',
                $this->callback(function (array $payload) {
                    return $payload['status'] === 'degraded';
                })
            );

        $job->run(new JobContext($this->logger, new JobSignal()));
    }

    public function testHandlePrintsToStdout(): void
    {
        $checker = $this->createAllUpChecker();
        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);

        ob_start();
        $job->run(new JobContext($this->logger, new JobSignal()));
        $output = ob_get_clean();

        $this->assertStringContainsString('[HealthCheck', $output);
        $this->assertStringContainsString('postgres=up', $output);
        $this->assertStringContainsString('redis=up', $output);
        $this->assertStringContainsString('rabbitmq=disabled', $output);
    }

    public function testHandleReturnsSuccess(): void
    {
        $checker = $this->createAllUpChecker();
        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);

        $result = $job->run(new JobContext($this->logger, new JobSignal()));

        $this->assertSame('health-check', $result->job);
        $this->assertSame('success', $result->status->value);
    }

    private function createAllUpChecker(): HealthCheckerInterface
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));
        return $checker;
    }
}
