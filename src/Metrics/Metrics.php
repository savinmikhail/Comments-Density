<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Metrics;

use SavinMikhail\CommentsDensity\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\DTO\Output\PerformanceMetricsDTO;

final class Metrics
{
    /**
     * @readonly
     */
    private CDS $cds;
    /**
     * @readonly
     */
    private ComToLoc $comToLoc;
    /**
     * @readonly
     */
    private PerformanceMonitor $performanceMonitor;
    public function __construct(CDS $cds, ComToLoc $comToLoc, PerformanceMonitor $performanceMonitor)
    {
        $this->cds = $cds;
        $this->comToLoc = $comToLoc;
        $this->performanceMonitor = $performanceMonitor;
    }

    public function startPerformanceMonitoring(): void
    {
        $this->performanceMonitor->start();
    }

    public function stopPerformanceMonitoring(): void
    {
        $this->performanceMonitor->stop();
    }

    public function getPerformanceMetrics(): PerformanceMetricsDTO
    {
        return $this->performanceMonitor->getPerformanceMetrics();
    }

    public function hasExceededThreshold(): bool
    {
        return $this->cds->hasExceededThreshold() || $this->comToLoc->hasExceededThreshold();
    }

    public function calculateCDS(array $commentStatistics): float
    {
        return $this->cds->calculateCDS($commentStatistics);
    }

    public function prepareCDS(float $cds): CdsDTO
    {
        return $this->cds->prepareCDS($cds);
    }

    public function prepareComToLoc(array $commentStatistics, int $linesOfCode): ComToLocDTO
    {
        return $this->comToLoc->prepareComToLoc($commentStatistics, $linesOfCode);
    }
}
