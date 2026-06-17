<?php

declare(strict_types=1);

$cloverFile = __DIR__ . '/../coverage/clover.xml';

if (!file_exists($cloverFile)) {
    echo "Erro: Arquivo coverage/clover.xml não encontrado. Execute os testes com cobertura primeiro.\n";
    exit(1);
}

$xml = new SimpleXMLElement(file_get_contents($cloverFile));
$metrics = $xml->xpath('//project/metrics');

if (!$metrics) {
    echo "Erro: Métricas não encontradas no arquivo clover.xml.\n";
    exit(1);
}

$totalElements = (int) $metrics[0]['elements'];
$coveredElements = (int) $metrics[0]['coveredelements'];

echo "\n--- Detalhes de Cobertura ---\n";

// Files that must have 100% coverage
$criticalPrefixes = [
    '/src/Core/BaseJob',
    '/src/Core/JobContext',
    '/src/Core/JobResult',
    '/src/Core/JobStatus',
    '/src/Core/JobSignal',
    '/src/Core/CronAdapter',
    '/src/Core/Dragonmantank',
    '/src/Core/JobInfo',
    '/src/Core/Exceptions/',
    '/src/Jobs/HealthCheck',
    '/src/Jobs/register',
    '/src/Shared/Config/',
    '/src/Shared/Utils/Logger',
    '/src/Infrastructure/Health/HealthCheckResult',
];

$exitCode = 0;
foreach ($xml->xpath('//file') as $file) {
    $fileMetrics = $file->metrics;
    $statements = (int) $fileMetrics['statements'];
    $coveredStatements = (int) $fileMetrics['coveredstatements'];
    $fileName = (string) $file['name'];

    if ($statements === 0) {
        continue;
    }

    $isCritical = false;
    foreach ($criticalPrefixes as $prefix) {
        if (str_contains($fileName, $prefix)) {
            $isCritical = true;
            break;
        }
    }

    $shortName = str_replace(realpath(__DIR__ . '/../'), '', $fileName);
    $percentage = ($coveredStatements / $statements) * 100;

    if ($isCritical && $percentage < 100) {
        $exitCode = 1;
        echo "❌ $shortName - $percentage% (critical)\n";
        foreach ($file->line as $line) {
            if ((string)$line['type'] === 'stmt' && (int)$line['count'] === 0) {
                echo "  File: $shortName\n";
                echo "  - Statements: $coveredStatements/$statements\n";
                echo "  - Uncovered lines: $line[num]\n";
            }
        }
    } elseif ($percentage < 100 && $coveredStatements < $statements) {
        echo "⚠️  $shortName - " . number_format($percentage, 2) . "% (non-critical)\n";
    }
}

$totalPercentage = $totalElements > 0 ? ($coveredElements / $totalElements) * 100 : 0;
echo "\nCobertura total: " . number_format($totalPercentage, 2) . "%\n";

if ($exitCode !== 0) {
    echo "❌ ERRO: Critical files with less than 100% coverage!\n";
    exit(1);
}

echo "✅ Sucesso: Todos os arquivos críticos com 100% de cobertura!\n";
exit(0);
