<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Metrics;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\PerformanceMetricsDTO;

final readonly class MetricsFacade
{
    public function __construct(
        private CDS $cds,
        private ComToLoc $comToLoc,
        private ResourceUtilization $performanceMonitor,
    ) {}

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
     */
    public function prepareComToLoc(array $commentStatistics, int $linesOfCode): ComToLocDTO
    {
        return $this->comToLoc->prepareComToLoc($commentStatistics, $linesOfCode);
    }
}
