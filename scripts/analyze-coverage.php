<?php

$xml = simplexml_load_file($argv[1]);
if (!$xml) {
    echo "Failed to load XML\n";
    exit(1);
}

$targets = ['Scheduler.php', 'DefaultHealthChecker.php', 'DatabaseProvider.php', 'RabbitMQProvider.php', 'SignalManager.php', 'RedisProvider.php', 'ShutdownHandler.php', 'HealthCheckResult.php'];

foreach ($xml->xpath('//file') as $file) {
    $name = (string)$file['name'];
    $basename = basename($name);
    if (!in_array($basename, $targets, true)) {
        continue;
    }

    $metrics = $file->metrics;
    echo "$basename: {$metrics['statements']} stmts, {$metrics['coveredstatements']} covered\n";
    foreach ($file->line as $line) {
        if ((string)$line['type'] === 'stmt' && (int)$line['count'] === 0) {
            echo "  Unc Line {$line['num']}\n";
        }
    }
}
