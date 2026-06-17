<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\BaseJob;
use App\Core\CronAdapter;
use App\Core\DragonmantankCronAdapter;
use App\Core\JobContext;
use App\Core\JobInfo;
use App\Core\Scheduler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SchedulerTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testListJobsReturnsInfo(): void
    {
        $job = $this->createEnabledJob('test', '*/5 * * * *');
        $cron = $this->createValidCron();
        $scheduler = new Scheduler([$job], $cron, $this->logger);

        $jobs = $scheduler->listJobs();
        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(JobInfo::class, $jobs[0]);
        $this->assertSame('test', $jobs[0]->name);
        $this->assertSame('*/5 * * * *', $jobs[0]->schedule);
        $this->assertTrue($jobs[0]->enabled);
        $this->assertSame('Test job', $jobs[0]->description);
    }

    public function testDuplicateJobNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate job name: dup');

        $job1 = $this->createEnabledJob('dup', '*/5 * * * *');
        $job2 = $this->createEnabledJob('dup', '*/10 * * * *');
        new Scheduler([$job1, $job2], $this->createValidCron(), $this->logger);
    }

    public function testStartWithInvalidCronThrows(): void
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('isValid')->willReturn(false);

        $scheduler = new Scheduler([$this->createEnabledJob('a', 'invalid')], $cron, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cron expression for job a: invalid');
        $scheduler->start();
    }

    public function testIsRunningReturnsCorrectState(): void
    {
        $cron = $this->createValidCron();
        $scheduler = new Scheduler([$this->createEnabledJob('a', '*/5 * * * *')], $cron, $this->logger);

        $this->assertFalse($scheduler->isRunning('a'));
    }

    public function testWaitForRunningJobsCompletes(): void
    {
        $cron = $this->createValidCron();
        $scheduler = new Scheduler([$this->createEnabledJob('a', '*/5 * * * *')], $cron, $this->logger);

        $scheduler->waitForRunningJobs();
        $this->assertTrue(true);
    }

    private function createEnabledJob(string $name, string $schedule): BaseJob
    {
        return new class($name, $schedule) extends BaseJob {
            public string $jobName;
            public string $jobSchedule;
            public string $jobDescription = 'Test job';

            public function __construct(string $name, string $schedule)
            {
                parent::__construct();
                $this->jobName = $name;
                $this->jobSchedule = $schedule;
            }

            public function getName(): string { return $this->jobName; }
            public function getSchedule(): string { return $this->jobSchedule; }
            public function getDescription(): string { return $this->jobDescription; }
            protected function handle(JobContext $context): void {}
        };
    }

    private function createValidCron(): CronAdapter
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('isValid')->willReturn(true);
        $cron->method('getNextRunDate')->willReturn(new \DateTimeImmutable('+1 minute'));
        return $cron;
    }
}
