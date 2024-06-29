<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Metrics;

use SavinMikhail\CommentsDensity\DTO\Output\ComToLocDTO;

use function array_sum;
use function round;

final class ComToLoc
{
    /**
     * @readonly
     */
    private array $thresholds;
    private bool $exceedThreshold = false;

    public function __construct(array $thresholds)
    {
        $this->thresholds = $thresholds;
    }

    public function prepareComToLoc(array $commentStatistics, int $linesOfCode): ComToLocDTO
    {
        $ratio = $this->getRatio($commentStatistics, $linesOfCode);
        return new ComToLocDTO(
            $ratio,
            $this->getColorForRatio($ratio)
        );
    }

    public function hasExceededThreshold(): bool
    {
        return $this->exceedThreshold;
    }

    private function getRatio(array $commentStatistics, int $linesOfCode): float
    {
        $totalComments = array_sum($commentStatistics);
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
