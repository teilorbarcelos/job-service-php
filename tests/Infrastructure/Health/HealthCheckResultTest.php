<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Health;

use App\Infrastructure\Health\HealthCheckResult;
use PHPUnit\Framework\TestCase;

class HealthCheckResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $result = new HealthCheckResult('up', 5);
        $this->assertSame('up', $result->status);
        $this->assertSame(5, $result->latencyMs);
        $this->assertNull($result->error);
    }

    public function testConstructorWithError(): void
    {
        $result = new HealthCheckResult('down', 100, 'connection failed');
        $this->assertSame('down', $result->status);
        $this->assertSame(100, $result->latencyMs);
        $this->assertSame('connection failed', $result->error);
    }

    public function testConstructorDisabled(): void
    {
        $result = new HealthCheckResult('disabled');
        $this->assertSame('disabled', $result->status);
        $this->assertNull($result->latencyMs);
        $this->assertNull($result->error);
    }
}
