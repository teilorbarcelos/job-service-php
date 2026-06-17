<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

use App\Core\DragonmantankCronAdapter;
use App\Infrastructure\Database\DatabaseProvider;
use App\Infrastructure\Redis\RedisProvider;
use App\Infrastructure\Messaging\RabbitMQProvider;
use App\Infrastructure\Health\DefaultHealthChecker;
use function App\Jobs\registerJobs;
use App\Shared\Config\EnvValidator;
use App\Shared\Utils\LoggerFactory;
use App\Shared\Utils\ShutdownHandler;
use Psr\Log\LoggerInterface;
use Monolog\Level;

$settings = EnvValidator::load();

$logger = LoggerFactory::create($settings->logLevel);
$logger->info('Starting job-service-php', ['env' => $settings->appEnv]);

$database = null;
$redis = null;

try {
    $database = DatabaseProvider::getInstance();
    $logger->info('Database provider initialized');
} catch (\Throwable $e) {
    $logger->warning('Failed to initialize database', ['error' => $e->getMessage()]);
}

try {
    $redis = RedisProvider::getInstance();
    $redis->getNativeRedis();
    $logger->info('Redis provider initialized');
} catch (\Throwable $e) {
    $logger->warning('Failed to initialize redis', ['error' => $e->getMessage()]);
}

$rabbitmq = new RabbitMQProvider($logger);
if ($settings->messagingEnabled) {
    try {
        $rabbitmq->connect();
        $logger->info('RabbitMQ connected');
    } catch (\Throwable $e) {
        $logger->warning('Failed to connect to RabbitMQ', ['error' => $e->getMessage()]);
    }
} else {
    $logger->info('Messaging disabled');
}

$checker = new DefaultHealthChecker(
    $database ?? DatabaseProvider::getInstance(),
    $redis ?? RedisProvider::getInstance(),
    $rabbitmq,
);
$cron = new DragonmantankCronAdapter();

$scheduler = registerJobs($settings, $cron, $logger, $checker);
$scheduler->start();

if (!function_exists('pcntl_signal')) {
    $logger->warning('pcntl extension not available, running without signal support');
}

ShutdownHandler::register(function () use ($scheduler, $rabbitmq, $logger): void {
    $logger->info('Shutting down...');
    $scheduler->stop();
    $scheduler->waitForRunningJobs();
    $rabbitmq->close();
    DatabaseProvider::close();
    RedisProvider::resetInstance();
    $logger->info('Shutdown complete');
}, $logger, $settings->shutdownTimeoutMs);

// @phpstan-ignore-next-line
while (true) {
    $scheduler->tick();
    sleep(1);
}
