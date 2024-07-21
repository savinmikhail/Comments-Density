<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Metrics;

use SavinMikhail\CommentsDensity\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\DTO\Output\PerformanceMetricsDTO;

final readonly class MetricsFacade
{
    public function __construct(
        private CDS $cds,
        private ComToLoc $comToLoc,
        private PerformanceMonitor $performanceMonitor
    ) {
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

    /**
     * @param CommentStatisticsDTO[] $commentStatistics
     * @return float
     */
    public function calculateCDS(array $commentStatistics): float
    {
        return $this->cds->calculateCDS($commentStatistics);
    }

    public function prepareCDS(float $cds): CdsDTO
    {
        return $this->cds->prepareCDS($cds);
    }

    /**
     * @param CommentStatisticsDTO[] $commentStatistics
     * @param int $linesOfCode
     * @return ComToLocDTO
     */
    public function prepareComToLoc(array $commentStatistics, int $linesOfCode): ComToLocDTO
    {
        return $this->comToLoc->prepareComToLoc($commentStatistics, $linesOfCode);
    }
}
