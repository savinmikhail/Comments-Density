<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Reporters;

use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\PerformanceMetricsDTO;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;

final readonly class ConsoleReporter implements ReporterInterface
{
    public function __construct(
        private OutputInterface $output,
    ) {}

    public function report(OutputDTO $dto): void
    {
        $this->printDetailedComments($dto->comments);
        $this->printTable($dto->commentsStatistics);
        $this->printComToLoc($dto->comToLocDTO);
        $this->printCDS($dto->cdsDTO);
        $this->printFilesAnalyzed($dto->filesAnalyzed);
        $this->printPerformanceMetrics($dto->performanceDTO);
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
