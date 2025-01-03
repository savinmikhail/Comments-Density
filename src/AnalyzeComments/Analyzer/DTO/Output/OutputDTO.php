<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output;

final readonly class OutputDTO
{
    public function __construct(
        public int $filesAnalyzed,
        /** @var array<array-key, CommentStatisticsDTO> */
        public array $commentsStatistics,
        /** @var array<array-key, CommentDTO> */
        public array $comments,
        public PerformanceMetricsDTO $performanceDTO,
        public ComToLocDTO $comToLocDTO,
        public CdsDTO $cdsDTO,
        public bool $exceedThreshold,
    ) {}
}
