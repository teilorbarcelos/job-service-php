<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\BaseJob;
use App\Core\CronAdapter;
use App\Core\JobContext;
use App\Core\JobInfo;
use App\Core\Scheduler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SchedulerTestJob extends BaseJob
{
    public string $jobName;
    public string $jobSchedule;
    public string $jobDescription = 'Test job';

    public function getName(): string { return $this->jobName; }
    public function getSchedule(): string { return $this->jobSchedule; }
    public function getDescription(): string { return $this->jobDescription; }
    protected function handle(JobContext $context): void {}
}

class SchedulerTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testListJobsReturnsInfo(): void
    {
        $job = new SchedulerTestJob();
        $job->jobName = 'test';
        $job->jobSchedule = '*/5 * * * *';
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

        $job1 = new SchedulerTestJob();
        $job1->jobName = 'dup';
        $job1->jobSchedule = '*/5 * * * *';

        $job2 = new SchedulerTestJob();
        $job2->jobName = 'dup';
        $job2->jobSchedule = '*/10 * * * *';

        new Scheduler([$job1, $job2], $this->createValidCron(), $this->logger);
    }

    public function testStartWithInvalidCronThrows(): void
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('isValid')->willReturn(false);

        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = 'invalid';

        $scheduler = new Scheduler([$job], $cron, $this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cron expression for job a: invalid');
        $scheduler->start();
    }

    public function testIsRunningReturnsCorrectState(): void
    {
        $cron = $this->createValidCron();
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $this->assertFalse($scheduler->isRunning('a'));
    }

    public function testWaitForRunningJobsCompletes(): void
    {
        $cron = $this->createValidCron();
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $scheduler->waitForRunningJobs();
        $this->assertTrue(true);
    }

    public function testStopDoesNotThrow(): void
    {
        $cron = $this->createValidCron();
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';
        $job->enabled = false;

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $scheduler->stop();
        $this->assertTrue(true);
    }

    private function createValidCron(): CronAdapter
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('isValid')->willReturn(true);
        $cron->method('getNextRunDate')->willReturn(new \DateTimeImmutable('+1 minute'));
        return $cron;
    }
}
