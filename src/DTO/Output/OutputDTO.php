<?php

namespace SavinMikhail\CommentsDensity\DTO\Output;

final class OutputDTO
{
    /**
     * @readonly
     */
    public int $filesAnalyzed;
    /**
     * @readonly
     */
    public array $commentsStatistics;
    /**
     * @readonly
     */
    public array $comments;
    /**
     * @readonly
     */
    public PerformanceMetricsDTO $performanceDTO;
    /**
     * @readonly
     */
    public ComToLocDTO $comToLocDTO;
    /**
     * @readonly
     */
    public CdsDTO $cdsDTO;
    /**
     * @readonly
     */
    public bool $exceedThreshold;
    public function __construct(int $filesAnalyzed, array $commentsStatistics, array $comments, PerformanceMetricsDTO $performanceDTO, ComToLocDTO $comToLocDTO, CdsDTO $cdsDTO, bool $exceedThreshold)
    {
        $this->filesAnalyzed = $filesAnalyzed;
        /** @var array<array-key, CommentStatisticsDTO> */
        $this->commentsStatistics = $commentsStatistics;
        /** @var array<array-key, CommentDTO> */
        $this->comments = $comments;
        $this->performanceDTO = $performanceDTO;
        $this->comToLocDTO = $comToLocDTO;
        $this->cdsDTO = $cdsDTO;
        $this->exceedThreshold = $exceedThreshold;
    }
}
