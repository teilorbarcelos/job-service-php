<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Database;

use App\Infrastructure\Database\DatabaseProvider;
use PHPUnit\Framework\TestCase;

class DatabaseProviderTest extends TestCase
{
    private static bool $sqliteAvailable;

    public static function setUpBeforeClass(): void
    {
        self::$sqliteAvailable = in_array('sqlite', \PDO::getAvailableDrivers());
    }

    protected function setUp(): void
    {
        DatabaseProvider::resetInstance();
    }

    public function testSingleton(): void
    {
        $instance1 = DatabaseProvider::getInstance();
        $instance2 = DatabaseProvider::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    public function testGetCapsule(): void
    {
        $provider = DatabaseProvider::getInstance();
        $capsule = $provider->getCapsule();
        $this->assertInstanceOf(\Illuminate\Database\Capsule\Manager::class, $capsule);
    }

    public function testPingReturnsTrueWithSqliteMemory(): void
    {
        if (!self::$sqliteAvailable) {
            $this->markTestSkipped('SQLite PDO driver not available');
        }
        $provider = DatabaseProvider::getInstance();
        $this->assertTrue($provider->ping());
    }

    public function testResetInstance(): void
    {
        $instance1 = DatabaseProvider::getInstance();
        DatabaseProvider::resetInstance();
        $instance2 = DatabaseProvider::getInstance();
        $this->assertNotSame($instance1, $instance2);
    }

    public function testClose(): void
    {
        DatabaseProvider::getInstance();
        DatabaseProvider::close();
        $this->assertTrue(true);
    }
}
