<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\CronAdapter;
use App\Core\DragonmantankCronAdapter;
use PHPUnit\Framework\TestCase;

class CronAdapterTest extends TestCase
{
    private CronAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new DragonmantankCronAdapter();
    }

    public function testIsValidReturnsTrueForValidExpression(): void
    {
        $this->assertTrue($this->adapter->isValid('*/5 * * * *'));
        $this->assertTrue($this->adapter->isValid('0 0 * * *'));
        $this->assertTrue($this->adapter->isValid('0 3 * * 1-5'));
    }

    public function testIsValidReturnsFalseForInvalidExpression(): void
    {
        $this->assertFalse($this->adapter->isValid('invalid'));
        $this->assertFalse($this->adapter->isValid(''));
        $this->assertFalse($this->adapter->isValid('* * * *'));
    }

    public function testGetNextRunDateReturnsDateTime(): void
    {
        $now = new \DateTimeImmutable('2025-01-01 00:00:00');
        $next = $this->adapter->getNextRunDate('*/5 * * * *', $now);

        $this->assertInstanceOf(\DateTimeImmutable::class, $next);
        $this->assertGreaterThan($now, $next);
    }

    public function testGetNextRunDateReturnsNullForInvalidExpression(): void
    {
        $now = new \DateTimeImmutable('2025-01-01 00:00:00');
        $next = $this->adapter->getNextRunDate('invalid', $now);

        $this->assertNull($next);
    }
}
