<?php

namespace SavinMikhail\CommentsDensity\DTO\Output;

final readonly class OutputDTO
{
    public function __construct(
        public int $filesAnalyzed,
        /** @var array<array-key, CommentStatisticsDTO> */
        public array $commentsStatistics,
        /** @var array<array-key, CommentDTO> */
        public array $comments,
        public PerformanceMetricsDTO $performanceMetricsDTO,
        public ComToLocDTO $comToLocDTO,
        public CdsDTO $cdsDTO,
    ) {
    }
}
