<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use InvalidArgumentException;

final readonly class StatisticCalculator
{
    private const WEIGHTS = [
        'docBlock' => 1,
        'missingDocblock' => -1,
        'regular' => -0.5,
        'todo' => -0.3,
        'fixme' => -0.3,
        'license' => 0,
    ];

    public function calculateCDS(array $commentStatistics): float
    {
        $rawScore = 0;

        foreach ($commentStatistics as $type => $count) {
            $weight = self::WEIGHTS[$type] ?? 0;
            $rawScore += $count * $weight;
        }

        $minPossibleScore = $this->getMinPossibleScore($commentStatistics);
        $maxPossibleScore = $this->getMaxPossibleScore($commentStatistics);

        return $this->scaleToRange($rawScore, $minPossibleScore, $maxPossibleScore);
    }

    private function getMinPossibleScore(array $commentStatistics): float
    {
        return self::WEIGHTS['regular'] * $commentStatistics['regular']
            + self::WEIGHTS['todo'] * $commentStatistics['todo']
            + self::WEIGHTS['fixme'] * $commentStatistics['fixme']
            + self::WEIGHTS['missingDocblock'] * $commentStatistics['missingDocblock']
            - self::WEIGHTS['docBlock'] * $commentStatistics['docBlock'];
    }

    private function getMaxPossibleScore(array $commentStatistics): float
    {
        return self::WEIGHTS['docBlock'] * (
                $commentStatistics['docBlock'] + $commentStatistics['missingDocblock']
            );
    }

    private function scaleToRange(float $value, float $min, float $max): float
    {
        if ($min >= $max) {
            throw new InvalidArgumentException("Minimum value must be less than maximum value.");
        }
        $scaledValue = ($value - $min) / ($max - $min);
        return $this->ensureInRange($scaledValue, 0, 1);
    }

    private function ensureInRange(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
