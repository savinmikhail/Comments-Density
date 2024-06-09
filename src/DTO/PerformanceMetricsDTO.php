<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO;

final readonly class PerformanceMetricsDTO {
    public function __construct(
        public float $executionTime,
        public float $peakMemoryUsage,
    ) {
    }
}