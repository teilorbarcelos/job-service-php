<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Log\LoggerInterface;

abstract class BaseJob
{
    abstract public function getName(): string;
    abstract public function getSchedule(): string;
    abstract public function getDescription(): string;
    public bool $enabled = true;

    protected ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    abstract protected function handle(JobContext $context): void;

    final public function run(JobContext $context): JobResult
    {
        if (!$this->enabled) {
            $this->getLogger()->debug('Job disabled, skipping execution', ['job' => $this->getName()]);
            return new JobResult($this->getName(), JobStatus::SUCCESS, 0);
        }

        $startedAt = hrtime(true);
        $this->getLogger()->info('Starting job', ['job' => $this->getName()]);

        try {
            $this->handle($context);
            $durationMs = (int)((hrtime(true) - $startedAt) / 1_000_000);
            $this->getLogger()->info('Job finished', ['job' => $this->getName(), 'duration_ms' => $durationMs]);
            return new JobResult($this->getName(), JobStatus::SUCCESS, $durationMs);
        } catch (JobCancelledException) {
            $durationMs = (int)((hrtime(true) - $startedAt) / 1_000_000);
            $this->getLogger()->warning('Job cancelled', ['job' => $this->getName(), 'duration_ms' => $durationMs]);
            return new JobResult($this->getName(), JobStatus::CANCELLED, $durationMs);
        } catch (\Throwable $e) {
            $durationMs = (int)((hrtime(true) - $startedAt) / 1_000_000);
            $this->getLogger()->error('Job failed', [
                'job' => $this->getName(),
                'duration_ms' => $durationMs,
                'error' => $e->getMessage(),
            ]);
            return new JobResult($this->getName(), JobStatus::FAILED, $durationMs, $e->getMessage());
        }
    }

    protected function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            throw new \RuntimeException('Logger not set. Call setLogger() before run().');
        }
        return $this->logger;
    }
}
