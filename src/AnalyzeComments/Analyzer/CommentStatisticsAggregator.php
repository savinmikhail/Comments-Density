<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentTypeFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;

use function substr_count;

use const PHP_EOL;

final readonly class CommentStatisticsAggregator
{
    public function __construct(
        private Config             $configDTO,
        private CommentTypeFactory $commentFactory,
    ) {}

    /**
     * @param CommentDTO[] $comments
     * @return CommentStatisticsDTO[]
     * @throws CommentsDensityException
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
            ++$lineCounts[$typeName]['count'];
        }

        return $lineCounts;
    }

    /**
     * @param array{'lines': int, 'count': int} $stat
     * @throws CommentsDensityException
     */
    private function prepareCommentStatistic(string $type, array $stat): CommentStatisticsDTO
    {
        $commentType = $this->commentFactory->getCommentType($type);
        if ($commentType) {
            return new CommentStatisticsDTO(
                $commentType->getColor(),
                $commentType->getName(),
                $stat['lines'],
                $commentType->getStatColor($stat['count'], $this->configDTO->thresholds),
                $stat['count'],
            );
        }

        throw new CommentsDensityException('Failed to classify comment of type ' . $type);
    }
}
