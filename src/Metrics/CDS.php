<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Metrics;

use InvalidArgumentException;
use Mikhail\PrimitiveWrappers\Int\Integer;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;

use function in_array;
use function round;

final class CDS
{
    private const MISSING_DOCBLOCK_WEIGHT = -1;

    private bool $exceedThreshold = false;

    /**
     * @param array<string, float> $thresholds
     */
    public function __construct(
        private readonly array $thresholds,
        private readonly CommentFactory $commentFactory,
    ) {
    }

    /**
     * @param CommentStatisticsDTO[] $commentStatistics
     * @return float
     */
    public function calculateCDS(array $commentStatistics): float
    {
        $rawScore = $this->calculateRawScore($commentStatistics);
        $minPossibleScore = $this->getMinPossibleScore($commentStatistics);
        $maxPossibleScore = $this->getMaxPossibleScore($commentStatistics);

        try {
            return (new Integer(0))
                ->scaleToRange($rawScore, $minPossibleScore, $maxPossibleScore);
        } catch (InvalidArgumentException) {
            return 0;
        }
    }

    /**
     * @param CommentStatisticsDTO[] $commentStatistics
     * @return float
     */
    private function calculateRawScore(array $commentStatistics): float
    {
        $rawScore = 0;

        foreach ($commentStatistics as $stat) {
            $comment = $this->commentFactory->getCommentType($stat->type);
            if ($comment) {
                $rawScore += $stat->count * $comment->getWeight();
                continue;
            }
            $rawScore += $stat->count * self::MISSING_DOCBLOCK_WEIGHT;
        }

        return $rawScore;
    }

    /**
     * @param CommentStatisticsDTO[] $commentStatistics
     * @return float
     */
    private function getMinPossibleScore(array $commentStatistics): float
    {
        $minScore = 0;
        foreach ($commentStatistics as $stat) {
            $comment = $this->commentFactory->getCommentType($stat->type);
            if (!$comment) {
                $minScore += self::MISSING_DOCBLOCK_WEIGHT * $stat->count;
                continue;
            }
            if ($comment->getWeight() < 0) {
                $minScore += $comment->getWeight() * $stat->count;
                continue;
            }
            $minScore -= $comment->getWeight() * $stat->count;
        }
        return $minScore;
    }

    /**
     * @param CommentStatisticsDTO[] $commentStatistics
     * @return float
     */
    private function getMaxPossibleScore(array $commentStatistics): float
    {
        $maxAmountOfDocBlock = 0;
        foreach ($commentStatistics as $statisticsDTO) {
            if (in_array($statisticsDTO->type, ['missingDocblock', 'docblock'])) {
                $maxAmountOfDocBlock += $statisticsDTO->count;
            }
        }
        return $maxAmountOfDocBlock * $this->commentFactory->getCommentType('docBlock')->getWeight();
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
