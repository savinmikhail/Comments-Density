<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use Mikhail\PrimitiveWrappers\Int\Integer;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\DocBlockComment;

final readonly class StatisticCalculator
{
    private const MISSING_DOCBLOCK_WEIGHT = -1;

    public function __construct(private CommentFactory $commentFactory)
    {
    }

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
            $comment = $this->commentFactory->getCommentType($type);
            if ($comment) {
                $rawScore += $count * $comment->getWeight();
                continue;
            }
            $rawScore += $count * self::MISSING_DOCBLOCK_WEIGHT;
        }

        return $rawScore;
    }

    private function getMinPossibleScore(array $commentStatistics): float
    {
        $minScore = 0;
        foreach ($commentStatistics as $type => $count) {
            $comment = $this->commentFactory->getCommentType($type);
            if (!$comment) {
                $minScore += self::MISSING_DOCBLOCK_WEIGHT * $count;
                continue;
            }
            if (in_array($comment->getAttitude(), ['bad', 'unwanted'])) {
                $minScore += $comment->getWeight() * $count;
                continue;
            }
            $minScore -= $comment->getWeight() * $count;
        }
        return $minScore;
    }

    private function getMaxPossibleScore(array $commentStatistics): float
    {
        return (
               ($commentStatistics['missingDocblock'] ?? 0)
               + ($commentStatistics['docblock'] ?? 0)
           )
           * ((new DocBlockComment())->getWeight());
    }
}
