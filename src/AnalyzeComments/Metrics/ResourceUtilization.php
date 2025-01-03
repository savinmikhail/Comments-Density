<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Metrics;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\PerformanceMetricsDTO;
use function memory_get_peak_usage;
use function microtime;
use function round;

final class ResourceUtilization
{
    private float $startTime;

    private float $endTime;

    private float $peakMemoryUsage;

    public function start(): void
    {
        $this->startTime = microtime(true);
    }

    public function stop(): void
    {
        $this->endTime = microtime(true);
        $this->peakMemoryUsage = memory_get_peak_usage(true);
    }

    public function getPerformanceMetrics(): PerformanceMetricsDTO
    {
        return new PerformanceMetricsDTO(
            $this->getExecutionTime(),
            $this->getPeakMemoryUsage(),
        );
    }

    private function getExecutionTime(): float
    {
        return round(($this->endTime - $this->startTime) * 1000, 2);
    }

    private function getPeakMemoryUsage(): float
    {
        return round($this->peakMemoryUsage / 1024 / 1024, 2); // Convert to MB
    }
}
