<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Metrics;

use Mikhail\PrimitiveWrappers\Int\Integer;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\DocBlockComment;
use SavinMikhail\CommentsDensity\DTO\Output\CdsDTO;

use function in_array;
use function round;

final class CDS
{
    private const MISSING_DOCBLOCK_WEIGHT = -1;

    private bool $exceedThreshold = false;

    public function __construct(
        private readonly array $thresholds,
        private readonly CommentFactory $commentFactory,
    ) {
    }

    public function calculateCDS(array $commentStatistics): float
    {
        $rawScore = $this->calculateRawScore($commentStatistics);
        $minPossibleScore = $this->getMinPossibleScore($commentStatistics);
        $maxPossibleScore = $this->getMaxPossibleScore($commentStatistics);

        try {
            return (new Integer(0))
                ->scaleToRange($rawScore, $minPossibleScore, $maxPossibleScore);
        } catch (\InvalidArgumentException) {
            return 0;
        }
    }

    private function calculateRawScore(array $commentStatistics): float
    {
        $rawScore = 0;

        foreach ($commentStatistics as $type => $stat) {
            $comment = $this->commentFactory->getCommentType($type);
            if ($comment) {
                $rawScore += $stat['count'] * $comment->getWeight();
                continue;
            }
            $rawScore += $stat['count'] * self::MISSING_DOCBLOCK_WEIGHT;
        }

        return $rawScore;
    }

    private function getMinPossibleScore(array $commentStatistics): float
    {
        $minScore = 0;
        foreach ($commentStatistics as $type => $stat) {
            $comment = $this->commentFactory->getCommentType($type);
            if (!$comment) {
                $minScore += self::MISSING_DOCBLOCK_WEIGHT * $stat['count'];
                continue;
            }
            if (in_array($comment->getAttitude(), ['bad', 'unwanted'])) {
                $minScore += $comment->getWeight() * $stat['count'];
                continue;
            }
            $minScore -= $comment->getWeight() * $stat['count'];
        }
        return $minScore;
    }

    private function getMaxPossibleScore(array $commentStatistics): float
    {
        return (
                ($commentStatistics['missingDocblock']['count'] ?? 0)
                + ($commentStatistics['docblock']['count'] ?? 0)
            )
            * ((new DocBlockComment())->getWeight());
    }

    public function prepareCDS(float $cds): CdsDTO
    {
        $cds = round($cds, 2);
        return new CdsDTO(
            $cds,
            $this->getColorForCDS($cds),
        );
    }

    private function getColorForCDS(float $cds): string
    {
        if (! isset($this->thresholds['CDS'])) {
            return 'white';
        }
        if ($cds >= $this->thresholds['CDS']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    public function hasExceededThreshold(): bool
    {
        return $this->exceedThreshold;
    }
}
