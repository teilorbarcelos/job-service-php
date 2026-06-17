<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\BaseJob;
use App\Core\JobContext;
use App\Core\JobResult;
use App\Core\JobSignal;
use App\Core\JobStatus;
use App\Core\JobCancelledException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TestableJob extends BaseJob
{
    public string $name;
    public string $schedule = '*/5 * * * *';
    public string $description = 'Test job';
    /** @var callable|null */
    public $handleCallback = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchedule(): string
    {
        return $this->schedule;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    protected function handle(JobContext $context): void
    {
        if ($this->handleCallback) {
            ($this->handleCallback)($context);
        }
    }
}

class BaseJobTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testRunReturnsSuccessWhenHandleSucceeds(): void
    {
        $job = new TestableJob();
        $job->name = 'test';
        $job->setLogger($this->logger);

        $result = $job->run(new JobContext($this->logger, new JobSignal()));

        $this->assertInstanceOf(JobResult::class, $result);
        $this->assertSame('test', $result->job);
        $this->assertSame(JobStatus::SUCCESS, $result->status);
    }

    public function testRunReturnsFailedWhenHandleThrows(): void
    {
        $job = new TestableJob();
        $job->name = 'test';
        $job->setLogger($this->logger);
        $job->handleCallback = function (): void {
            throw new \RuntimeException('something broke');
        };

        $result = $job->run(new JobContext($this->logger, new JobSignal()));

        $this->assertSame('test', $result->job);
        $this->assertSame(JobStatus::FAILED, $result->status);
        $this->assertSame('something broke', $result->error);
    }

    public function testRunReturnsCancelledWhenSignalAborted(): void
    {
        $job = new TestableJob();
        $job->name = 'test';
        $job->setLogger($this->logger);
        $job->handleCallback = function (JobContext $ctx): void {
            $ctx->signal->throwIfAborted();
        };

        $signal = new JobSignal();
        $signal->abort();

        $result = $job->run(new JobContext($this->logger, $signal));

        $this->assertSame('test', $result->job);
        $this->assertSame(JobStatus::CANCELLED, $result->status);
    }

    public function testRunSkipsWhenDisabled(): void
    {
        $job = new TestableJob();
        $job->name = 'test';
        $job->enabled = false;
        $job->setLogger($this->logger);

        $result = $job->run(new JobContext($this->logger, new JobSignal()));

        $this->assertSame('test', $result->job);
        $this->assertSame(JobStatus::SUCCESS, $result->status);
        $this->assertSame(0, $result->durationMs);
    }

    public function testRunReturnsDuration(): void
    {
        $job = new TestableJob();
        $job->name = 'test';
        $job->setLogger($this->logger);
        $job->handleCallback = function (): void {
            usleep(1_000);
        };

        $result = $job->run(new JobContext($this->logger, new JobSignal()));

        $this->assertGreaterThan(0, $result->durationMs);
    }

    public function testGetLoggerThrowsWhenNotSet(): void
    {
        $job = new TestableJob();
        $job->name = 'test';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Logger not set');
        $job->run(new JobContext($this->logger, new JobSignal()));
    }
}
