<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

use function array_keys;
use function array_map;
use function array_sum;
use function file;
use function file_get_contents;
use function round;
use function substr_count;

use const PHP_EOL;

final class CommentDensity
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly array $thresholds,
        private readonly MissingDocBlockAnalyzer $docBlockAnalyzer,
        private readonly StatisticCalculator $statisticCalculator,
        private readonly CommentFactory $commentFactory
    ) {
    }

    public function analyzeDirectory(string $directory): bool
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        $comments = [];
        $commentStatistics = [];
        $totalLinesOfCode = 0;
        $cdsSum = 0;
        $filesAnalyzed = 0;
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            // Check if the file is a PHP file
            if (! $this->isPhpFile($file)) {
                continue;
            }
            if (! $this->isFileReadable($file)) {
                continue;
            }
            $filename = $file->getRealPath();

            $this->output->writeln("<info>Analyzing $filename</info>");
            $statistics = $this->getStatistics($filename);
            $this->updateCommentStatistics($commentStatistics, $statistics);

            $totalLinesOfCode += $statistics['linesOfCode'];
            $cdsSum += $statistics['CDS'];
            $filesAnalyzed++;
            array_push($comments, ...$statistics['comments']);
        }
        $this->printStatistics($commentStatistics, $totalLinesOfCode, $cdsSum / $filesAnalyzed, $comments);
        return $this->exceedThreshold;
    }

    private function updateCommentStatistics(array &$commentStatistics, array $statistics): void
    {
        foreach ($statistics['commentStatistic'] as $type => $count) {
            if (!isset($commentStatistics[$type])) {
                $commentStatistics[$type] = 0;
            }
            $commentStatistics[$type] += $count;
        }
    }

    private function isPhpFile(SplFileInfo $file): bool
    {
        return $file->isFile() && $file->getExtension() === 'php';
    }

    private function isFileReadable(SplFileInfo $file): bool
    {
        if (! $file->isReadable()) {
            $this->output->writeln("<highlight>{$file->getRealPath()} is not readable</highlight>");
            return false;
        }
        return true;
    }

    private function getStatistics(string $filename): array
    {
        $code = file_get_contents($filename);
        $tokens = token_get_all($code);

        $comments = $this->getCommentsFromFile($tokens, $filename);
        $missingDocBlocks = $this
            ->docBlockAnalyzer
            ->getMissingDocblocks($tokens, $filename);
        $commentStatistic = $this->countCommentLines($comments);
        $commentStatistic['missingDocblock'] = count($missingDocBlocks);
        $comments = array_merge($missingDocBlocks, $comments);
        $linesOfCode = $this->countTotalLines($filename);
        $cds = $this
            ->statisticCalculator
            ->calculateCDS($commentStatistic);

        return [
            'comments' => $comments,
            'commentStatistic' => $commentStatistic,
            'linesOfCode' => $linesOfCode,
            'CDS' => $cds
        ];
    }

    private function printStatistics(array $commentStatistics, int $linesOfCode, float $cds, array $comments): void
    {
        $this->printDetailedComments($comments);
        $this->printTable($commentStatistics);
        $this->printComToLoc($commentStatistics, $linesOfCode);
        $this->printCDS($cds);
    }

    private function printDetailedComments(array $comments): void
    {
        foreach ($comments as $comment) {
            if ($comment['type'] === 'missingDocblock') {
                $this->output->writeln(
                    "<fg=red>{$comment['type']} comment</> in "
                    . "<fg=blue>{$comment['file']}</>:"
                    . "<fg=blue>{$comment['line']}</>    "
                    . "{$comment['content']}"
                );
                continue;
            }
            if ($comment['type']->getAttitude() === 'good') {
                continue;
            }
            $this->output->writeln(
                "<fg={$comment['type']->getColor()}>{$comment['type']->getName()} comment</> in "
                . "<fg=blue>{$comment['file']}</>:"
                . "<fg=blue>{$comment['line']}</>    "
                . "<fg=yellow>{$comment['content']}</>"
            );
        }
    }

    private function getRatio(array $commentStatistics, int $linesOfCode): float
    {
        $totalComments = array_sum($commentStatistics);
        return round($totalComments / $linesOfCode, 2);
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

    private function getColorForRatio(float $ratio): string
    {
        if (! isset($this->thresholds['Com/LoC'])) {
            return 'white';
        }
        if ($ratio >= $this->thresholds['Com/LoC']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    private function getCommentsFromFile(array $tokens, string $filename): array
    {
        $comments = [];
        foreach ($tokens as $token) {
            if (! is_array($token)) {
                continue;
            }
            if (! in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }
            $commentType = $this->commentFactory->classifyComment($token[1]);
            if ($commentType) {
                $comments[] = [
                   'content' => $token[1],
                    'type' => $commentType,
                    'line' => $token[2],
                    'file' => $filename
                ];
            }
        }
        return $comments;
    }

    private function countCommentLines(array $comments): array
    {
        $lineCounts = [];
        foreach ($comments as $comment) {
            $lineCounts[$comment['type']->getName()] += substr_count($comment['content'], PHP_EOL) + 1;
        }
        return $lineCounts;
    }

    private function countTotalLines(string $filename): int
    {
        $fileContent = file($filename);
        return count($fileContent);
    }

    /**
     * @param array $commentStatistics
     * @return void
     */
    public function printTable(array $commentStatistics): void
    {
        $table = new Table($this->output);
        $table
            ->setHeaders(['Comment Type', 'Lines'])
            ->setRows(
                array_map(function (string $type, int $count): array {
                    if ($type === 'missingDocblock') {
                        $color = $this->getMissingDocBlockStatColor($count);
                        return [
                            "<fg=" . $this->getMissingDocBlockColor() . ">$type</>", "<fg=$color>$count</>"
                        ];
                    }
                    $commentType = $this->commentFactory->getCommentType($type);
                    if ($commentType) {
                        $color = $commentType->getStatColor($count, $this->thresholds);
                        return ["<fg=" . $commentType->getColor() . ">$type</>", "<fg=$color>$count</>"];
                    }
                    return [$type, $count];
                }, array_keys($commentStatistics), $commentStatistics)
            );

        $table->render();
    }

    private function getMissingDocBlockStatColor(float $count): string
    {
        if (! isset($this->thresholds['missingDocBlock'])) {
            return 'white';
        }
        if ($count <= $this->thresholds['missingDocBlock']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    private function getMissingDocBlockColor(): string
    {
        return 'red';
    }

    /**
     * @param array $commentStatistics
     * @param int $linesOfCode
     * @return void
     */
    public function printComToLoc(array $commentStatistics, int $linesOfCode): void
    {
        $ratio = $this->getRatio($commentStatistics, $linesOfCode);
        $color = $this->getColorForRatio($ratio);
        $this->output->writeln(["<fg=$color>Com/LoC: $ratio</>"]);
    }

    /**
     * @param float $cds
     * @return void
     */
    public function printCDS(float $cds): void
    {
        $cds = round($cds, 2);
        $color = $this->getColorForCDS($cds);
        $this->output->writeln(["<fg=$color>CDS: $cds</>"]);
    }
}
