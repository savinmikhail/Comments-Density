<?php

declare(strict_types=1);

$iterations = 10;
$times = [];
$memories = [];

for ($i = 0; $i < $iterations; ++$i) {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    exec('php ./../bin/comments_density analyze:comments', $output, $returnVar);

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    $times[] = $endTime - $startTime;
    $memories[] = $endMemory - $startMemory;

    if ($returnVar !== 0) {
        echo "Command failed with status: {$returnVar}" . PHP_EOL;
    }
}

$averageTime = array_sum($times) / $iterations;
$averageMemory = array_sum($memories) / $iterations;

echo 'Average execution time: ' . ($averageTime * 1000) . ' ms' . PHP_EOL;
echo 'Average memory usage: ' . ($averageMemory / 1024 / 1024) . ' MB' . PHP_EOL;
