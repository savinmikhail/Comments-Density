<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Output;

final class PerformanceMetricsDTO
{
    /**
     * @readonly
     */
    public float $executionTime;
    /**
     * @readonly
     */
    public float $peakMemoryUsage;
    public function __construct(float $executionTime, float $peakMemoryUsage)
    {
        $this->executionTime = $executionTime;
        $this->peakMemoryUsage = $peakMemoryUsage;
    }
}
