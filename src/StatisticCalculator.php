<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use Mikhail\PrimitiveWrappers\Int\Integer;

final readonly class StatisticCalculator
{
    private const WEIGHTS = [
        CommentType::DOCBLOCK->value => 1,
        CommentType::MISSING_DOCBLOCK->value => -1,
        CommentType::REGULAR->value => -1,
        CommentType::TODO->value => -0.3,
        CommentType::FIXME->value => -0.3,
        CommentType::LICENSE->value => 0,
    ];

    public function calculateCDS(array $commentStatistics): float
    {
        $rawScore = $this->calculateRawScore($commentStatistics);
        $minPossibleScore = $this->getMinPossibleScore($commentStatistics);
        $maxPossibleScore = $this->getMaxPossibleScore($commentStatistics);

        return (new Integer(0))
            ->scaleToRange($rawScore, $minPossibleScore, $maxPossibleScore);
    }

    private function calculateRawScore(array $commentStatistics): float
    {
        $rawScore = 0;

        foreach ($commentStatistics as $type => $count) {
            $weight = self::WEIGHTS[$type] ?? 0;
            $rawScore += $count * $weight;
        }

        return $rawScore;
    }

    private function getMinPossibleScore(array $commentStatistics): float
    {
        return self::WEIGHTS[CommentType::REGULAR->value]
            * ($commentStatistics[CommentType::REGULAR->value] ?? 0)

            + self::WEIGHTS[CommentType::TODO->value]
            * ($commentStatistics[CommentType::TODO->value] ?? 0)

            + self::WEIGHTS[CommentType::FIXME->value]
            * ($commentStatistics[CommentType::FIXME->value] ?? 0)

            + self::WEIGHTS[CommentType::MISSING_DOCBLOCK->value]
            * ($commentStatistics[CommentType::MISSING_DOCBLOCK->value] ?? 0)

            - self::WEIGHTS[CommentType::DOCBLOCK->value]
            * ($commentStatistics[CommentType::DOCBLOCK->value] ?? 0);
    }

    private function getMaxPossibleScore(array $commentStatistics): float
    {
        return self::WEIGHTS[CommentType::DOCBLOCK->value] * (
                ($commentStatistics[CommentType::DOCBLOCK->value] ?? 0)
                + ($commentStatistics[CommentType::MISSING_DOCBLOCK->value] ?? 0)
            );
    }
}
