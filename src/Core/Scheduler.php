<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Log\LoggerInterface;

class Scheduler
{
    /** @var array<string, BaseJob> */
    private array $jobs = [];
    /** @var array<string, \DateTimeImmutable> */
    private array $nextRuns = [];
    /** @var array<string, bool> */
    private array $running = [];
    private bool $stopped = false;
    private CronAdapter $cron;
    private int $executionTimeoutMs;
    private LoggerInterface $logger;

    /** @param BaseJob[] $jobs */
    public function __construct(
        array $jobs,
        CronAdapter $cron,
        LoggerInterface $logger,
        int $executionTimeoutMs = 300000,
    ) {
        $this->cron = $cron;
        $this->logger = $logger;
        $this->executionTimeoutMs = $executionTimeoutMs;

        foreach ($jobs as $job) {
            $name = $job->getName();
            if (isset($this->jobs[$name])) {
                throw new \InvalidArgumentException("Duplicate job name: {$name}");
            }
            $job->setLogger($logger);
            $this->jobs[$name] = $job;
        }
    }

    /** @return JobInfo[] */
    public function listJobs(): array
    {
        $result = [];
        foreach ($this->jobs as $job) {
            $result[] = new JobInfo(
                $job->getName(),
                $job->getSchedule(),
                $job->enabled,
                $job->getDescription(),
            );
        }
        return $result;
    }

    public function start(): void
    {
        foreach ($this->jobs as $name => $job) {
            if (!$job->enabled) {
                $this->logger->info('Job disabled, will not be scheduled', ['job' => $name]);
                continue;
            }

            if (!$this->cron->isValid($job->getSchedule())) {
                throw new \InvalidArgumentException(
                    "Invalid cron expression for job {$name}: {$job->getSchedule()}"
                );
            }

            $this->nextRuns[$name] = $this->calculateNextRun($job->getSchedule());
            $this->logger->info('Job scheduled', [
                'job' => $name,
                'schedule' => $job->getSchedule(),
                'description' => $job->getDescription(),
            ]);
        }

        $this->stopped = false;
        $this->runLoop();
    }

    public function stop(): void
    {
        $this->stopped = true;
        foreach ($this->jobs as $name => $job) {
            $this->logger->info('Job stopped', ['job' => $name]);
        }
    }

    public function waitForRunningJobs(): void
    {
        while (count($this->running) > 0) {
            usleep(50_000);
        }
    }

    public function isRunning(string $name): bool
    {
        return ($this->running[$name] ?? false);
    }

    private function runLoop(): void
    {
        if (!function_exists('pcntl_signal')) {
            $this->logger->warning('pcntl extension not available, running without signal support');
        }

        while (!$this->stopped) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            $now = new \DateTimeImmutable();
            foreach ($this->jobs as $name => $job) {
                if (!$job->enabled) {
                    continue;
                }

                $nextRun = $this->nextRuns[$name] ?? null;
                if ($nextRun === null || $now >= $nextRun) {
                    if ($this->isRunning($name)) {
                        $this->logger->warning("Job {$name} still running, skipping");
                        $this->nextRuns[$name] = $this->calculateNextRun($job->getSchedule());
                        continue;
                    }

                    $this->execute($name, $job);
                    $this->nextRuns[$name] = $this->calculateNextRun($job->getSchedule());
                }
            }

            // Sleep for 1 second (or next run, whichever is sooner)
            sleep(1);
        }
    }

    private function execute(string $name, BaseJob $job): void
    {
        $this->running[$name] = true;

        try {
            $signal = new JobSignal();

            if (function_exists('pcntl_signal') && function_exists('pcntl_alarm')) {
                $alarmHandler = function () use ($signal): void {
                    $signal->abort();
                };
                pcntl_signal(SIGALRM, $alarmHandler);
                pcntl_alarm((int)ceil($this->executionTimeoutMs / 1000));
            }

            $context = new JobContext(
                $this->getJobLogger($name),
                $signal,
            );

            $job->run($context);

            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }
        } finally {
            unset($this->running[$name]);
        }
    }

    private function getJobLogger(string $name): LoggerInterface
    {
        if (method_exists($this->logger, 'withName')) {
            return $this->logger->withName($name);
        }
        return $this->logger;
    }

    private function calculateNextRun(string $schedule): ?\DateTimeImmutable
    {
        return $this->cron->getNextRunDate($schedule, new \DateTimeImmutable());
    }
}
