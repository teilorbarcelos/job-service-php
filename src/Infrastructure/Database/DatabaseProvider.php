<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseProvider
{
    private static ?self $instance = null;
    private Capsule $capsule;

    protected function __construct()
    {
        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver' => $_ENV['DB_DRIVER'] ?? 'sqlite',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_DATABASE'] ?? ':memory:',
            'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
            'password' => $_ENV['DB_PASSWORD'] ?? 'postgrespw',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8',
            'prefix' => '',
        ]);

        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function getCapsule(): Capsule
    {
        return $this->capsule;
    }

    public function ping(): bool
    {
        try {
            $this->capsule->getConnection()->getPdo()->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function close(): void
    {
        if (self::$instance !== null) {
            self::$instance->capsule->getConnection()->disconnect();
        }
        self::$instance = null;
    }
}
