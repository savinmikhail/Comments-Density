<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Analyzer;

use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\Exception\CommentsDensityException;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;

use function substr_count;

use const PHP_EOL;

final readonly class CommentStatisticsAggregator
{
    public function __construct(
        private ConfigDTO $configDTO,
        private CommentFactory $commentFactory,
        private MissingDocBlockAnalyzer $missingDocBlock,
    ) {
    }

    /**
     * @param CommentDTO[] $comments
     * @return CommentStatisticsDTO[]
     */
    public function calculateCommentStatistics(array $comments): array
    {
        $occurrences = $this->countCommentOccurrences($comments);
        $preparedStatistics = [];
        foreach ($occurrences as $type => $stat) {
            $preparedStatistics[] = $this->prepareCommentStatistic($type, $stat);
        }

        return $preparedStatistics;
    }

    /**
     * @param CommentDTO[] $comments
     * @return array<string, array{'lines': int, 'count': int}>
     */
    private function countCommentOccurrences(array $comments): array
    {
        $lineCounts = [];
        foreach ($comments as $comment) {
            $typeName = $comment->commentType;
            if (!isset($lineCounts[$typeName])) {
                $lineCounts[$typeName] = [
                    'lines' => 0,
                    'count' => 0,
                ];
            }
            $lineCounts[$typeName]['lines'] += substr_count($comment->content, PHP_EOL) + 1;
            $lineCounts[$typeName]['count']++;
        }
        return $lineCounts;
    }

    /**
     * @param string $type
     * @param array{'lines': int, 'count': int} $stat
     * @return CommentStatisticsDTO
     */
    private function prepareCommentStatistic(string $type, array $stat): CommentStatisticsDTO
    {
        if ($type === $this->missingDocBlock->getName()) {
            return new CommentStatisticsDTO(
                $this->missingDocBlock->getColor(),
                $this->missingDocBlock->getName(),
                $stat['lines'],
                $this->missingDocBlock->getStatColor($stat['count'], $this->configDTO->thresholds),
                $stat['count']
            );
        }

        $commentType = $this->commentFactory->getCommentType($type);
        if ($commentType) {
            return new CommentStatisticsDTO(
                $commentType->getColor(),
                $commentType->getName(),
                $stat['lines'],
                $commentType->getStatColor($stat['count'], $this->configDTO->thresholds),
                $stat['count']
            );
        }

        throw new CommentsDensityException('Failed to classify comment of type ' . $type);
    }
}
