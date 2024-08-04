<?php

require __DIR__ . '/../vendor/autoload.php';

$iterations = 10; // Number of iterations for the test
$times = [];
$memories = [];

for ($i = 0; $i < $iterations; $i++) {
    // Capture the start time and memory usage
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    // Run the command
    exec('php ./../bin/comments_density analyze:comments', $output, $returnVar);

    // Capture the end time and memory usage
    $endTime = microtime(true);
    $endMemory = memory_get_usage();


    // Calculate the execution time and memory usage for this iteration
    $times[] = $endTime - $startTime;
    $memories[] = $endMemory - $startMemory;

    // Check if the command ran successfully
    if ($returnVar !== 0) {
        echo "Command failed with status: $returnVar" . PHP_EOL;
    }
}

// Calculate the average execution time and memory usage
$averageTime = array_sum($times) / $iterations;
$averageMemory = array_sum($memories) / $iterations;

echo "Average execution time: " . ($averageTime * 1000) . " ms" . PHP_EOL;
echo "Average memory usage: " . ($averageMemory / 1024 / 1024) . " MB" . PHP_EOL;
