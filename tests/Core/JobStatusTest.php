<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\JobStatus;
use PHPUnit\Framework\TestCase;

class JobStatusTest extends TestCase
{
    public function testAllCasesAreStrings(): void
    {
        $this->assertSame('success', JobStatus::SUCCESS->value);
        $this->assertSame('failed', JobStatus::FAILED->value);
        $this->assertSame('cancelled', JobStatus::CANCELLED->value);
        $this->assertSame('timeout', JobStatus::TIMEOUT->value);
    }

    public function testFromReturnsCorrectCase(): void
    {
        $this->assertSame(JobStatus::SUCCESS, JobStatus::from('success'));
        $this->assertSame(JobStatus::FAILED, JobStatus::from('failed'));
        $this->assertSame(JobStatus::CANCELLED, JobStatus::from('cancelled'));
        $this->assertSame(JobStatus::TIMEOUT, JobStatus::from('timeout'));
    }

    public function testFromInvalidThrows(): void
    {
        $this->expectException(\ValueError::class);
        JobStatus::from('invalid');
    }

    public function testCasesCount(): void
    {
        $this->assertCount(4, JobStatus::cases());
    }
}
