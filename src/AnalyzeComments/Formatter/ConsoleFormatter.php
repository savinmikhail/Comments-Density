<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Formatter;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\PerformanceMetricsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\Report;
use SavinMikhail\CommentsDensity\AnalyzeComments\Formatter\Filter\ViolatingCommentsOnlyFilter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;

final readonly class ConsoleFormatter implements FormatterInterface
{
    public function __construct(
        private OutputInterface $output,
        private ViolatingCommentsOnlyFilter $violatingCommentsOnlyFilter = new ViolatingCommentsOnlyFilter(),
    ) {}

    public function report(Report $report): void
    {
        $this->printDetailedComments($this->violatingCommentsOnlyFilter->filter($report));
        $this->printTable($report->commentsStatistics);
        $this->printComToLoc($report->comToLocDTO);
        $this->printCDS($report->cdsDTO);
        $this->printFilesAnalyzed($report->filesAnalyzed);
        $this->printPerformanceMetrics($report->performanceDTO);
    }

    private function printFilesAnalyzed(int $filesAnalyzed): void
    {
        $this->output->writeln("<fg=white>Files analyzed: {$filesAnalyzed}</>");
    }

    /**
     * @param CommentDTO[] $comments
     */
    private function printDetailedComments(array $comments): void
    {
        foreach ($comments as $commentDTO) {
            $this->output->writeln(
                "<fg={$commentDTO->commentTypeColor}>{$commentDTO->commentType} comment</> in "
                . "<fg=blue>{$commentDTO->file}</>:"
                . "<fg=blue>{$commentDTO->line}</>    "
                . "<fg=yellow>{$commentDTO->content}</>",
            );
        }
    }

    private function printPerformanceMetrics(PerformanceMetricsDTO $dto): void
    {
        $this->output->writeln("<fg=white>Time: {$dto->executionTime} ms, Memory: {$dto->peakMemoryUsage} MB</>");
    }

    private function printComToLoc(ComToLocDTO $dto): void
    {
        $this->output->writeln(["<fg={$dto->color}>Com/LoC: {$dto->comToLoc}</>"]);
    }

    private function printCDS(CdsDTO $dto): void
    {
        $this->output->writeln(["<fg={$dto->color}>CDS: {$dto->cds}</>"]);
    }

    /**
     * @param CommentStatisticsDTO[] $commentStatistics
     */
    private function printTable(array $commentStatistics): void
    {
        $table = new Table($this->output);
        $table
            ->setHeaders(['Comment Type', 'Lines', 'Times'])
            ->setRows(
                array_map(static fn(CommentStatisticsDTO $dto): array => [
                    '<fg=' . $dto->typeColor . ">{$dto->type}</>",
                    "<fg=white>{$dto->lines}</>",
                    "<fg={$dto->color}>{$dto->count}</>",
                ], $commentStatistics),
            );

        $table->render();
    }
}
