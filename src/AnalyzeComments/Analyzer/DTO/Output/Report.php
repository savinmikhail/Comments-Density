<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output;

final readonly class Report
{
    public function __construct(
        public int $filesAnalyzed,
        /** @var CommentStatisticsDTO[] */
        public array $commentsStatistics,
        /** @var CommentDTO[] */
        public array $comments,
        public PerformanceMetricsDTO $performanceDTO,
        public ComToLocDTO $comToLocDTO,
        public CdsDTO $cdsDTO,
        public bool $exceedThreshold,
    ) {}
}
