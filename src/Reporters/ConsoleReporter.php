<?php

namespace SavinMikhail\CommentsDensity\Reporters;

use SavinMikhail\CommentsDensity\DTO\CdsDTO;
use SavinMikhail\CommentsDensity\DTO\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\ComToLocDTO;
use SavinMikhail\CommentsDensity\DTO\OutputDTO;
use SavinMikhail\CommentsDensity\DTO\PerformanceMetricsDTO;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ConsoleReporter
{
    public function __construct(
        private OutputInterface $output,
        private OutputDTO $dto,
    ) {
    }

    public function report(): void
    {
        $this->printDetailedComments($this->dto->comments);
        $this->printTable($this->dto->commentsStatistics);
        $this->printComToLoc($this->dto->comToLocDTO);
        $this->printCDS($this->dto->cdsDTO);
        $this->printFilesAnalyzed($this->dto->filesAnalyzed);
        $this->printPerformanceMetrics($this->dto->performanceMetricsDTO);
    }

    private function printFilesAnalyzed(int $filesAnalyzed): void
    {
        $this->output->writeln("<fg=white>Files analyzed: $filesAnalyzed</>");
    }

    private function printDetailedComments(array $comments): void
    {
        /** @var CommentDTO $commentDTO */
        foreach ($comments as $commentDTO) {
            $this->output->writeln(
                "<fg=$commentDTO->commentTypeColor>$commentDTO->commentType comment</> in "
                . "<fg=blue>$commentDTO->file</>:"
                . "<fg=blue>$commentDTO->line</>    "
                . "<fg=yellow>$commentDTO->content</>"
            );
        }
    }

    private function printPerformanceMetrics(PerformanceMetricsDTO $dto): void
    {
        $this->output->writeln("<fg=white>Time: $dto->executionTime ms, Memory: $dto->peakMemoryUsage MB</>");
    }

    private function printComToLoc(ComToLocDTO $dto): void
    {
        $this->output->writeln(["<fg=$dto->color>Com/LoC: $dto->comToLoc</>"]);
    }

    private function printCDS(CdsDTO $dto): void
    {
        $this->output->writeln(["<fg=$dto->color>CDS: $dto->cds</>"]);
    }

    private function printTable(array $commentStatistics): void
    {
        $table = new Table($this->output);
        $table
            ->setHeaders(['Comment Type', 'Lines'])
            ->setRows(
                array_map(function (int $key, CommentStatisticsDTO $dto): array {
                    return ["<fg=" . $dto->typeColor . ">$dto->type</>", "<fg=$dto->color>$dto->count</>"];
                }, array_keys($commentStatistics), $commentStatistics)
            );

        $table->render();
    }

}