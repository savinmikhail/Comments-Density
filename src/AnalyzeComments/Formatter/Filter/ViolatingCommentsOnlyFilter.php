<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Formatter\Filter;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\Report;

use function in_array;

final readonly class ViolatingCommentsOnlyFilter
{
    /**
     * @return CommentDTO[]
     */
    public function filter(Report $report): array
    {
        // Identify comment types with threshold violations
        $violatingTypes = array_map(
            static fn(CommentStatisticsDTO $commentStatisticsDTO): string => $commentStatisticsDTO->type,
            array_filter(
                $report->commentsStatistics,
                static fn(CommentStatisticsDTO $commentStatisticsDTO): bool => $commentStatisticsDTO->color === 'red',
            ),
        );

        return array_filter(
            $report->comments,
            static fn(CommentDTO $commentDTO): bool => in_array($commentDTO->commentType, $violatingTypes, true)
                && $commentDTO->commentTypeColor === 'red',
        );
    }
}
