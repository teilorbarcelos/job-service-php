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
    public bool $shouldThrow = false;
    public bool $wasExecuted = false;

    public function getName(): string { return $this->jobName; }
    public function getSchedule(): string { return $this->jobSchedule; }
    public function getDescription(): string { return $this->jobDescription; }
    protected function handle(JobContext $context): void
    {
        $this->wasExecuted = true;
        if ($this->shouldThrow) {
            throw new \RuntimeException('job error');
        }
    }
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

    public function testListJobsMultiple(): void
    {
        $j1 = new SchedulerTestJob();
        $j1->jobName = 'a';
        $j1->jobSchedule = '*/5 * * * *';
        $j2 = new SchedulerTestJob();
        $j2->jobName = 'b';
        $j2->jobSchedule = '*/10 * * * *';

        $scheduler = new Scheduler([$j1, $j2], $this->createValidCron(), $this->logger);
        $this->assertCount(2, $scheduler->listJobs());
    }

    public function testDuplicateJobNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate job name: dup');

        $j1 = new SchedulerTestJob();
        $j1->jobName = 'dup';
        $j1->jobSchedule = '*/5 * * * *';
        $j2 = new SchedulerTestJob();
        $j2->jobName = 'dup';
        $j2->jobSchedule = '*/10 * * * *';

        new Scheduler([$j1, $j2], $this->createValidCron(), $this->logger);
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

    public function testStartValidatesAndSetsNextRuns(): void
    {
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';

        $scheduler = new Scheduler([$job], $this->createValidCron(), $this->logger);
        $scheduler->start();

        $this->assertNotNull($scheduler->getNextRun('a'));
    }

    public function testIsRunningReturnsCorrectState(): void
    {
        $scheduler = new Scheduler([], $this->createValidCron(), $this->logger);
        $this->assertFalse($scheduler->isRunning('nonexistent'));
    }

    public function testWaitForRunningJobsCompletes(): void
    {
        $scheduler = new Scheduler([], $this->createValidCron(), $this->logger);
        $scheduler->waitForRunningJobs();
        $this->assertTrue(true);
    }

    public function testStopDoesNotThrow(): void
    {
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';
        $job->enabled = false;

        $scheduler = new Scheduler([$job], $this->createValidCron(), $this->logger);
        $scheduler->start();
        $scheduler->stop();
        $this->assertTrue(true);
    }

    public function testExecuteRunsJob(): void
    {
        $cron = $this->createValidCron();
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';
        $job->setLogger($this->logger);

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $scheduler->execute('a', $job);

        $this->assertTrue($job->wasExecuted);
        $this->assertFalse($scheduler->isRunning('a'));
    }

    public function testExecuteHandlesException(): void
    {
        $cron = $this->createValidCron();
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';
        $job->shouldThrow = true;
        $job->setLogger($this->logger);

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $scheduler->execute('a', $job);

        $this->assertTrue($job->wasExecuted);
        $this->assertFalse($scheduler->isRunning('a'));
    }

    public function testCalculateNextRunReturnsDateTime(): void
    {
        $cron = new \App\Core\DragonmantankCronAdapter();
        $scheduler = new Scheduler([], $cron, $this->logger);

        $next = $scheduler->calculateNextRun('*/5 * * * *');
        $this->assertInstanceOf(\DateTimeImmutable::class, $next);
    }

    public function testCalculateNextRunReturnsNullForInvalid(): void
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('getNextRunDate')->willReturn(null);

        $scheduler = new Scheduler([], $cron, $this->logger);
        $this->assertNull($scheduler->calculateNextRun('invalid'));
    }

    public function testGetNextRunReturnsNullBeforeStart(): void
    {
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';

        $scheduler = new Scheduler([$job], $this->createValidCron(), $this->logger);
        $this->assertNull($scheduler->getNextRun('a'));
    }

    public function testGetJobLoggerReturnsLogger(): void
    {
        $scheduler = new Scheduler([], $this->createValidCron(), $this->logger);
        $logger = $scheduler->getJobLogger('test');
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testStartWithDisabledJob(): void
    {
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';
        $job->enabled = false;

        $scheduler = new Scheduler([$job], $this->createValidCron(), $this->logger);
        $scheduler->start();

        $this->assertNull($scheduler->getNextRun('a'));
    }

    public function testTickRunsEnabledJobs(): void
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('isValid')->willReturn(true);
        $cron->method('getNextRunDate')->willReturn(new \DateTimeImmutable('-1 minute'));

        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/1 * * * *';
        $job->setLogger($this->logger);

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $scheduler->start();
        $scheduler->tick();

        $this->assertTrue($job->wasExecuted);
    }

    public function testTickDoesNotRunDisabledJobs(): void
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('isValid')->willReturn(true);
        $cron->method('getNextRunDate')->willReturn(new \DateTimeImmutable('-1 minute'));

        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/1 * * * *';
        $job->enabled = false;
        $job->setLogger($this->logger);

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $scheduler->start();
        $scheduler->tick();

        $this->assertFalse($job->wasExecuted);
    }

    public function testTickWithNoNextRunYetDoesNotExecute(): void
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('isValid')->willReturn(true);
        $cron->method('getNextRunDate')->willReturn(new \DateTimeImmutable('+5 minutes'));

        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/1 * * * *';
        $job->setLogger($this->logger);

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $scheduler->start();
        $scheduler->tick();

        $this->assertFalse($job->wasExecuted);
    }

    public function testTickWithNullNextRunExecutes(): void
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('isValid')->willReturn(true);
        $cron->method('getNextRunDate')->willReturn(null);

        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/1 * * * *';
        $job->setLogger($this->logger);

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $scheduler->start();
        $scheduler->tick();

        $this->assertTrue($job->wasExecuted);
    }

    public function testStopWithJobs(): void
    {
        $cron = $this->createValidCron();
        $job = new SchedulerTestJob();
        $job->jobName = 'a';
        $job->jobSchedule = '*/5 * * * *';

        $scheduler = new Scheduler([$job], $cron, $this->logger);
        $scheduler->start();
        $scheduler->stop();

        $this->assertTrue(true);
    }

    public function testSetStopped(): void
    {
        $scheduler = new Scheduler([], $this->createValidCron(), $this->logger);
        $scheduler->setStopped(true);
        $this->assertTrue(true);
    }

    public function testWaitForRunningJobsWithNoJobs(): void
    {
        $scheduler = new Scheduler([], $this->createValidCron(), $this->logger);
        $scheduler->waitForRunningJobs();
        $this->assertTrue(true);
    }

    public function testGetJobLoggerWithStandardLogger(): void
    {
        $scheduler = new Scheduler([], $this->createValidCron(), $this->logger);
        $logger = $scheduler->getJobLogger('test');
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    private function createValidCron(): CronAdapter
    {
        $cron = $this->createMock(CronAdapter::class);
        $cron->method('isValid')->willReturn(true);
        $cron->method('getNextRunDate')->willReturn(new \DateTimeImmutable('+1 minute'));
        return $cron;
    }
}
