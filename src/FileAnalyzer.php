<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Metrics\CDS;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

use function array_merge;
use function array_push;
use function file;
use function file_get_contents;
use function in_array;
use function is_array;
use function substr_count;
use function token_get_all;

use const PHP_EOL;
use const T_COMMENT;
use const T_DOC_COMMENT;

final class FileAnalyzer
{
    /**
     * @readonly
     */
    private OutputInterface $output;
    /**
     * @readonly
     */
    private MissingDocBlockAnalyzer $docBlockAnalyzer;
    /**
     * @readonly
     */
    private CDS $statisticCalculator;
    /**
     * @readonly
     */
    private CommentFactory $commentFactory;
    public function __construct(
        OutputInterface $output,
        MissingDocBlockAnalyzer $docBlockAnalyzer,
        CDS $statisticCalculator,
        CommentFactory $commentFactory
    ) {
        $this->output = $output;
        $this->docBlockAnalyzer = $docBlockAnalyzer;
        $this->statisticCalculator = $statisticCalculator;
        $this->commentFactory = $commentFactory;
    }

    public function analyzeFile(
        SplFileInfo $file,
        array &$commentStatistics,
        array &$comments,
        int &$totalLinesOfCode,
        float &$cdsSum
    ): void {
        if (! $this->isPhpFile($file) || ! $this->isFileReadable($file)) {
            return;
        }

        $filename = $file->getRealPath();
        $this->output->writeln("<info>Analyzing $filename</info>");

        $statistics = $this->getStatistics($filename);
        $this->updateCommentStatistics($commentStatistics, $statistics);

        $totalLinesOfCode += $statistics['linesOfCode'];
        $cdsSum += $statistics['CDS'];
        array_push($comments, ...$statistics['comments']);
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

    private function updateCommentStatistics(array &$commentStatistics, array $statistics): void
    {
        foreach ($statistics['commentStatistic'] as $type => $count) {
            if (!isset($commentStatistics[$type])) {
                $commentStatistics[$type] = 0;
            }
            $commentStatistics[$type] += $count;
        }
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
            $typeName = $comment['type']->getName();
            if (!isset($lineCounts[$typeName])) {
                $lineCounts[$typeName] = 0;
            }
            $lineCounts[$typeName] += substr_count($comment['content'], PHP_EOL) + 1;
        }
        return $lineCounts;
    }

    private function countTotalLines(string $filename): int
    {
        $fileContent = file($filename);
        return count($fileContent);
    }
}
