<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Metrics;

use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\ComToLocDTO;

use function round;

final class ComToLoc
{
    private bool $exceedThreshold = false;

    /**
     * @param array<string, float> $thresholds
     */
    public function __construct(private readonly array $thresholds) {}

    /**
     * @param CommentStatisticsDTO[] $commentStatistics
     */
    public function prepareComToLoc(array $commentStatistics, int $linesOfCode): ComToLocDTO
    {
        $ratio = $this->getRatio($commentStatistics, $linesOfCode);

        return new ComToLocDTO(
            $ratio,
            $this->getColorForRatio($ratio),
        );
    }

    public function hasExceededThreshold(): bool
    {
        return $this->exceedThreshold;
    }

    /**
     * @param CommentStatisticsDTO[] $commentStatistics
     */
    private function getRatio(array $commentStatistics, int $linesOfCode): float
    {
        if ($linesOfCode === 0) {
            return 0;
        }

        $totalComments = 0;

        foreach ($commentStatistics as $stat) {
            $totalComments += $stat->lines;
        }

        return round($totalComments / $linesOfCode, 2);
    }

    private function getColorForRatio(float $ratio): string
    {
        if (! isset($this->thresholds['Com/LoC'])) {
            return 'white';
        }
        if ($ratio >= $this->thresholds['Com/LoC']) {
            return 'green';
        }
        $this->exceedThreshold = true;

        return 'red';
    }
}
