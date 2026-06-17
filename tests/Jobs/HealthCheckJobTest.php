<?php

declare(strict_types=1);

namespace Tests\Jobs;

use App\Core\JobContext;
use App\Core\JobSignal;
use App\Infrastructure\Health\HealthCheckResult;
use App\Infrastructure\Health\HealthCheckerInterface;
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

    public function testDefaultSchedule(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $job = new HealthCheckJob($checker);
        $this->assertSame('*/1 * * * *', $job->getSchedule());
    }

    public function testDefaultEnabled(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $job = new HealthCheckJob($checker);
        $this->assertTrue($job->enabled);
    }

    public function testHandleChecksAllThreeServices(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->expects($this->once())->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->expects($this->once())->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->expects($this->once())->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);

        $result = $job->run(new JobContext($this->logger, new JobSignal()));

        $this->assertSame('success', $result->status->value);
    }

    public function testHandleLogsHealthyWhenAllUp(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));

        $loggedPayloads = [];
        $this->logger->method('info')->willReturnCallback(function ($message, $context) use (&$loggedPayloads): void {
            $loggedPayloads[] = $context;
        });

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);
        $job->run(new JobContext($this->logger, new JobSignal()));

        $healthPayload = null;
        foreach ($loggedPayloads as $p) {
            if (($p['event'] ?? '') === 'health-check') {
                $healthPayload = $p;
                break;
            }
        }

        $this->assertNotNull($healthPayload);
        $this->assertSame('healthy', $healthPayload['status']);
    }

    public function testHandleLogsDegradedWhenPostgresDown(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('down', null, 'refused'));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));

        $loggedPayloads = [];
        $this->logger->method('info')->willReturnCallback(function ($message, $context) use (&$loggedPayloads): void {
            $loggedPayloads[] = $context;
        });

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);
        $job->run(new JobContext($this->logger, new JobSignal()));

        $healthPayload = null;
        foreach ($loggedPayloads as $p) {
            if (($p['event'] ?? '') === 'health-check') {
                $healthPayload = $p;
                break;
            }
        }

        $this->assertNotNull($healthPayload);
        $this->assertSame('degraded', $healthPayload['status']);
    }

    public function testHandleLogsDegradedWhenRedisDown(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('down', null, 'timeout'));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));

        $loggedPayloads = [];
        $this->logger->method('info')->willReturnCallback(function ($message, $context) use (&$loggedPayloads): void {
            $loggedPayloads[] = $context;
        });

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);
        $job->run(new JobContext($this->logger, new JobSignal()));

        $healthPayload = null;
        foreach ($loggedPayloads as $p) {
            if (($p['event'] ?? '') === 'health-check') {
                $healthPayload = $p;
                break;
            }
        }

        $this->assertNotNull($healthPayload);
        $this->assertSame('degraded', $healthPayload['status']);
    }

    public function testHandleLogsDegradedWhenRabbitMQDown(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('down', null, 'not connected'));

        $loggedPayloads = [];
        $this->logger->method('info')->willReturnCallback(function ($message, $context) use (&$loggedPayloads): void {
            $loggedPayloads[] = $context;
        });

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);
        $job->run(new JobContext($this->logger, new JobSignal()));

        $healthPayload = null;
        foreach ($loggedPayloads as $p) {
            if (($p['event'] ?? '') === 'health-check') {
                $healthPayload = $p;
                break;
            }
        }

        $this->assertNotNull($healthPayload);
        $this->assertSame('degraded', $healthPayload['status']);
    }

    public function testHandlePrintsToStdout(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));

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

    public function testHandleReturnsSuccessResult(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);

        $result = $job->run(new JobContext($this->logger, new JobSignal()));

        $this->assertSame('health-check', $result->job);
        $this->assertSame('success', $result->status->value);
    }

    public function testHandleTimestampInPayload(): void
    {
        $checker = $this->createMock(HealthCheckerInterface::class);
        $checker->method('checkPostgres')->willReturn(new HealthCheckResult('up', 5));
        $checker->method('checkRedis')->willReturn(new HealthCheckResult('up', 1));
        $checker->method('checkRabbitMQ')->willReturn(new HealthCheckResult('disabled'));

        $loggedPayloads = [];
        $this->logger->method('info')->willReturnCallback(function ($message, $context) use (&$loggedPayloads): void {
            $loggedPayloads[] = $context;
        });

        $job = new HealthCheckJob($checker);
        $job->setLogger($this->logger);
        $job->run(new JobContext($this->logger, new JobSignal()));

        $healthPayload = null;
        foreach ($loggedPayloads as $p) {
            if (($p['event'] ?? '') === 'health-check') {
                $healthPayload = $p;
                break;
            }
        }

        $this->assertNotNull($healthPayload);
        $this->assertNotNull($healthPayload['timestamp']);
        $this->assertNotNull($healthPayload['timestamp']);
        $this->assertStringContainsString('T', $healthPayload['timestamp']);
    }
}
