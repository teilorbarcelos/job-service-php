<?php

declare(strict_types=1);

namespace Tests\Jobs;

use App\Core\DragonmantankCronAdapter;
use App\Core\Scheduler;
use App\Infrastructure\Health\DefaultHealthChecker;
use App\Shared\Config\AppSettings;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use function App\Jobs\registerJobs;

class RegisterJobsTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testRegisterJobsReturnsSchedulerWithHealthCheckJob(): void
    {
        $settings = new AppSettings(
            appEnv: 'testing',
            appDebug: false,
            logLevel: 'error',
            shutdownTimeoutMs: 30000,
            jobExecutionTimeoutMs: 300000,
            dbDriver: 'sqlite',
            dbHost: 'localhost',
            dbPort: 5432,
            dbDatabase: ':memory:',
            dbUsername: 'postgres',
            dbPassword: 'postgrespw',
            redisHost: 'localhost',
            redisPort: 6379,
            messagingEnabled: false,
            rabbitHost: 'localhost',
            rabbitPort: 5672,
            rabbitUser: 'guest',
            rabbitPassword: 'guest',
            healthCheckCron: '*/5 * * * *',
            healthCheckEnabled: true,
        );

        $cron = new DragonmantankCronAdapter();
        $checker = $this->createMock(DefaultHealthChecker::class);

        $scheduler = registerJobs($settings, $cron, $this->logger, $checker);

        $this->assertInstanceOf(Scheduler::class, $scheduler);
        $jobs = $scheduler->listJobs();
        $this->assertCount(1, $jobs);
        $this->assertSame('health-check', $jobs[0]->name);
        $this->assertSame('*/5 * * * *', $jobs[0]->schedule);
        $this->assertTrue($jobs[0]->enabled);
    }

    public function testRegisterJobsWithDisabledHealthCheck(): void
    {
        $settings = new AppSettings(
            appEnv: 'testing',
            appDebug: false,
            logLevel: 'error',
            shutdownTimeoutMs: 30000,
            jobExecutionTimeoutMs: 300000,
            dbDriver: 'sqlite',
            dbHost: 'localhost',
            dbPort: 5432,
            dbDatabase: ':memory:',
            dbUsername: 'postgres',
            dbPassword: 'postgrespw',
            redisHost: 'localhost',
            redisPort: 6379,
            messagingEnabled: false,
            rabbitHost: 'localhost',
            rabbitPort: 5672,
            rabbitUser: 'guest',
            rabbitPassword: 'guest',
            healthCheckCron: '*/5 * * * *',
            healthCheckEnabled: false,
        );

        $cron = new DragonmantankCronAdapter();
        $checker = $this->createMock(DefaultHealthChecker::class);

        $scheduler = registerJobs($settings, $cron, $this->logger, $checker);

        $jobs = $scheduler->listJobs();
        $this->assertCount(1, $jobs);
        $this->assertFalse($jobs[0]->enabled);
    }
}
