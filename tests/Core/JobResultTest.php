<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\JobResult;
use App\Core\JobStatus;
use PHPUnit\Framework\TestCase;

class JobResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $result = new JobResult('test', JobStatus::SUCCESS, 100);
        $this->assertSame('test', $result->job);
        $this->assertSame(JobStatus::SUCCESS, $result->status);
        $this->assertSame(100, $result->durationMs);
        $this->assertNull($result->error);
    }

    public function testConstructorWithError(): void
    {
        $result = new JobResult('test', JobStatus::FAILED, 50, 'something went wrong');
        $this->assertSame('test', $result->job);
        $this->assertSame(JobStatus::FAILED, $result->status);
        $this->assertSame(50, $result->durationMs);
        $this->assertSame('something went wrong', $result->error);
    }

    public function testToArray(): void
    {
        $result = new JobResult('test', JobStatus::SUCCESS, 100);
        $this->assertSame([
            'job' => 'test',
            'status' => 'success',
            'duration_ms' => 100,
            'error' => null,
        ], $result->toArray());
    }

    public function testToArrayWithError(): void
    {
        $result = new JobResult('test', JobStatus::FAILED, 50, 'error msg');
        $this->assertSame([
            'job' => 'test',
            'status' => 'failed',
            'duration_ms' => 50,
            'error' => 'error msg',
        ], $result->toArray());
    }
}
