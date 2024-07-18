<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Analyzer;

use Generator;
use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\CommentTypeInterface;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

use function array_merge;
use function array_push;
use function file;
use function file_get_contents;
use function in_array;
use function is_array;
use function str_contains;
use function substr_count;
use function token_get_all;

use const PHP_EOL;
use const T_COMMENT;
use const T_DOC_COMMENT;

final class Analyzer
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly ConfigDTO $configDTO,
        private readonly CommentFactory $commentFactory,
        private readonly MissingDocBlockAnalyzer $missingDocBlock,
        private readonly MetricsFacade $metrics,
        private readonly OutputInterface $output,
        private readonly MissingDocBlockAnalyzer $docBlockAnalyzer,
        private readonly BaselineStorageInterface $baselineStorage,
    ) {
    }

    public function analyze(Generator $files): OutputDTO
    {
        $this->metrics->startPerformanceMonitoring();
        $comments = [];
        $totalLinesOfCode = 0;
        $filesAnalyzed = 0;

        foreach ($files as $file) {
            if ($this->shouldSkipFile($file)) {
                continue;
            }

            $commentsAndLines = $this->analyzeFile($file->getRealPath());
            $totalLinesOfCode += $commentsAndLines['linesOfCode'];
            array_push($comments, ...$commentsAndLines['comments']);

            $filesAnalyzed++;
        }

        if ($this->configDTO->useBaseline) {
            $comments = $this->baselineStorage->filterComments($comments);
        }

        $commentStatistics = $this->countCommentOccurrences($comments);

        return $this->createOutputDTO($comments, $commentStatistics, $totalLinesOfCode, $filesAnalyzed);
    }

    private function shouldSkipFile(SplFileInfo $file): bool
    {
        return
            $this->isInWhitelist($file->getRealPath()) ||
            $file->getSize() === 0 ||
            !$this->isPhpFile($file) ||
            !$file->isReadable();
    }

    private function analyzeFile(string $filename): array
    {
        $this->output->writeln("<info>Analyzing $filename</info>");

        $code = file_get_contents($filename);
        $tokens = token_get_all($code);

        $comments = $this->getCommentsFromFile($tokens, $filename);
        if ($this->shouldAnalyzeMissingDocBlocks()) {
            $missingDocBlocks = $this->docBlockAnalyzer->getMissingDocblocks($code, $filename);
            $comments = array_merge($missingDocBlocks, $comments);
        }

        $linesOfCode = $this->countTotalLines($filename);

        return [
            'comments' => $comments,
            'linesOfCode' => $linesOfCode,
        ];
    }

    private function shouldAnalyzeMissingDocBlocks(): bool
    {
        return
            empty($this->configDTO->only)
            || in_array($this->missingDocBlock->getName(), $this->configDTO->only, true);
    }

    private function getCommentsFromFile(array $tokens, string $filename): array
    {
        $comments = [];
        foreach ($tokens as $token) {
            if (!is_array($token) || !in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
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

    private function countTotalLines(string $filename): int
    {
        $fileContent = file($filename);
        return count($fileContent);
    }

    private function isPhpFile(SplFileInfo $file): bool
    {
        return $file->isFile() && $file->getExtension() === 'php';
    }

    private function countCommentOccurrences(array $comments): array
    {
        $lineCounts = [];
        foreach ($comments as $comment) {
            $typeName = (string)$comment['type'];
            if (!isset($lineCounts[$typeName])) {
                $lineCounts[$typeName] = [
                    'lines' => 0,
                    'count' => 0,
                ];
            }
            $lineCounts[$typeName]['lines'] += substr_count($comment['content'], PHP_EOL) + 1;
            $lineCounts[$typeName]['count']++;
        }
        return $lineCounts;
    }

    private function checkThresholdsExceeded(): bool
    {
        if ($this->metrics->hasExceededThreshold()) {
            return true;
        }
        if ($this->missingDocBlock->hasExceededThreshold()) {
            return true;
        }
        foreach ($this->commentFactory->getCommentTypes() as $commentType) {
            if ($commentType->hasExceededThreshold()) {
                return true;
            }
        }
        return false;
    }

    private function createOutputDTO(
        array $comments,
        array $commentStatistics,
        int $totalLinesOfCode,
        int $filesAnalyzed
    ): OutputDTO {
        $preparedStatistics = $this->prepareCommentStatistics($commentStatistics);
        $preparedComments = $this->prepareComments($comments);
        $comToLoc = $this->metrics->prepareComToLoc($commentStatistics, $totalLinesOfCode);
        $cds = $this->metrics->prepareCDS($this->metrics->calculateCDS($commentStatistics));
        $this->exceedThreshold = $this->checkThresholdsExceeded();
        $this->metrics->stopPerformanceMonitoring();
        $performanceMetrics = $this->metrics->getPerformanceMetrics();

        return new OutputDTO(
            $filesAnalyzed,
            $preparedStatistics,
            $preparedComments,
            $performanceMetrics,
            $comToLoc,
            $cds,
            $this->exceedThreshold
        );
    }

    private function prepareCommentStatistics(array $commentStatistics): array
    {
        $preparedStatistics = [];
        foreach ($commentStatistics as $type => $stat) {
            $preparedStatistics[] = $this->prepareCommentStatistic($type, $stat);
        }
        return $preparedStatistics;
    }

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

        return new CommentStatisticsDTO('', $type, $stat['lines'], '', $stat['count']);
    }

    private function prepareComments(array $comments): array
    {
        $preparedComments = [];
        foreach ($comments as $comment) {
            /** @var CommentTypeInterface|string $commentType */
            $commentType = $comment['type'];
            if ($commentType === $this->missingDocBlock->getName()) {
                $preparedComments[] = new CommentDTO(
                    $this->missingDocBlock->getName(),
                    $this->missingDocBlock->getColor(),
                    $comment['file'],
                    $comment['line'],
                    $comment['content']
                );
            } elseif ($commentType->getWeight() <= 0) {
                $preparedComments[] = new CommentDTO(
                    $commentType->getName(),
                    $commentType->getColor(),
                    $comment['file'],
                    $comment['line'],
                    $comment['content']
                );
            }
        }
        return $preparedComments;
    }

    private function isInWhitelist(string $filePath): bool
    {
        foreach ($this->configDTO->exclude as $whitelistedDir) {
            if (str_contains($filePath, $whitelistedDir)) {
                return true;
            }
        }
        return false;
    }
}
