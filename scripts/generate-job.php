<?php

declare(strict_types=1);

if ($argc < 2) {
    echo "Usage: php scripts/generate-job.php Name [schedule] [description]\n";
    echo "\n";
    echo "Examples:\n";
    echo "  php scripts/generate-job.php CleanupOldRecords\n";
    echo "  php scripts/generate-job.php CleanupOldRecords \"0 3 * * *\" \"Remove records older than 90 days\"\n";
    exit(1);
}

$rawName = $argv[1];
$schedule = $argv[2] ?? '0 3 * * *';
$description = $argv[3] ?? 'TBD';

$pascal = str_replace(['-', '_', ' '], '', ucwords($rawName, '-_ '));
if (str_ends_with($pascal, 'Job')) {
    $pascal = substr($pascal, 0, -3);
}
$className = $pascal . 'Job';
$kebabName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $pascal));

$jobDir = __DIR__ . '/../src/Jobs';
$testDir = __DIR__ . '/../tests/Jobs';
$registerPath = __DIR__ . '/../src/Jobs/register-jobs.php';

echo "Generating job:\n";
echo "  Class:       {$className}\n";
echo "  name:        {$kebabName}\n";
echo "  schedule:    {$schedule}\n";
echo "  description: {$description}\n";
echo "\n";

// Create directories
if (!is_dir($jobDir)) {
    mkdir($jobDir, 0755, true);
}
if (!is_dir($testDir)) {
    mkdir($testDir, 0755, true);
}

// Template for Job
$jobContent = <<<PHP
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\BaseJob;
use App\Core\JobContext;

class {$className} extends BaseJob
{
    public string \$schedule = '{$schedule}';

    public function __construct()
    {
    }

    public function getName(): string
    {
        return '{$kebabName}';
    }

    public function getSchedule(): string
    {
        return \$this->schedule;
    }

    public function getDescription(): string
    {
        return '{$description}';
    }

    protected function handle(JobContext \$context): void
    {
        \$context->logger->info('Processing job', ['job' => \$this->getName()]);
    }
}

PHP;

// Template for Test
$testContent = <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Jobs;

use App\Core\JobContext;
use App\Core\JobSignal;
use App\Jobs\\{$className};
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class {$className}Test extends TestCase
{
    private LoggerInterface \$logger;

    protected function setUp(): void
    {
        \$this->logger = \$this->createMock(LoggerInterface::class);
    }

    public function testGetName(): void
    {
        \$job = new {$className}();
        \$this->assertSame('{$kebabName}', \$job->getName());
    }

    public function testGetSchedule(): void
    {
        \$job = new {$className}();
        \$this->assertSame('{$schedule}', \$job->getSchedule());
    }

    public function testGetDescription(): void
    {
        \$job = new {$className}();
        \$this->assertSame('{$description}', \$job->getDescription());
    }

    public function testHandleSucceeds(): void
    {
        \$job = new {$className}();
        \$job->setLogger(\$this->logger);

        \$result = \$job->run(new JobContext(\$this->logger, new JobSignal()));
        \$this->assertSame('{$kebabName}', \$result->job);
        \$this->assertSame('success', \$result->status->value);
    }

    public function testHandleWhenDisabled(): void
    {
        \$job = new {$className}();
        \$job->enabled = false;
        \$job->setLogger(\$this->logger);

        \$result = \$job->run(new JobContext(\$this->logger, new JobSignal()));
        \$this->assertSame('{$kebabName}', \$result->job);
        \$this->assertSame('success', \$result->status->value);
        \$this->assertSame(0, \$result->durationMs);
    }
}

PHP;

// Write files
$jobFile = "{$jobDir}/{$className}.php";
if (file_exists($jobFile)) {
    echo "Error: File already exists: {$jobFile}\n";
    exit(1);
}
file_put_contents($jobFile, $jobContent);
echo "  ✓ {$jobFile}\n";

$testFile = "{$testDir}/{$className}Test.php";
if (file_exists($testFile)) {
    echo "Warning: Test file already exists, skipping: {$testFile}\n";
} else {
    file_put_contents($testFile, $testContent);
    echo "  ✓ {$testFile}\n";
}

// Update register-jobs.php
$registerContent = file_get_contents($registerPath);

$importLine = "use App\\Jobs\\{$className};";
$jobLine = "        \$jobs[] = new {$className}();";

if (!str_contains($registerContent, $importLine)) {
    $registerContent = str_replace(
        '// [GENERATOR_IMPORTS]',
        "{$importLine}\n// [GENERATOR_IMPORTS]",
        $registerContent
    );
    $registerContent = str_replace(
        '// [GENERATOR_JOBS]',
        "{$jobLine}\n        // [GENERATOR_JOBS]",
        $registerContent
    );
    file_put_contents($registerPath, $registerContent);
    echo "  ✓ Registered in register-jobs.php\n";
} else {
    echo "  Already registered in register-jobs.php\n";
}

echo "\n✅ Generation complete!\n";
echo "\nNext steps:\n";
echo "  1. Implement handle() in src/Jobs/{$className}.php\n";
echo "  2. Run: make coverage\n";
echo "  3. Run: make dev (to see it scheduled)\n";
