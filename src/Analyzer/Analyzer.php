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
    private int $totalLinesOfCode = 0;

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
        $filesAnalyzed = 0;

        foreach ($files as $file) {
            if ($this->shouldSkipFile($file)) {
                continue;
            }

            $newComments = $this->analyzeFile($file->getRealPath());
            array_push($comments, ...$newComments);

            $filesAnalyzed++;
        }

        if ($this->configDTO->useBaseline) {
            $comments = $this->baselineStorage->filterComments($comments);
        }

        $commentStatistics = $this->calculateCommentStatistics($comments);

        return $this->createOutputDTO($comments, $commentStatistics, $filesAnalyzed);
    }

    /**
     * @param CommentDTO[] $comments
     * @return CommentStatisticsDTO[]
     */
    private function calculateCommentStatistics(array $comments): array
    {
        $occurrences = $this->countCommentOccurrences($comments);
        $preparedStatistics = [];
        foreach ($occurrences as $type => $stat) {
            $preparedStatistics[] = $this->prepareCommentStatistic($type, $stat);
        }

        return $preparedStatistics;
    }

    private function shouldSkipFile(SplFileInfo $file): bool
    {
        return
            $this->isInWhitelist($file->getRealPath()) ||
            $file->getSize() === 0 ||
            !$this->isPhpFile($file) ||
            !$file->isReadable();
    }

    /**
     * @param string $filename
     * @return CommentDTO[]
     */
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

        $this->totalLinesOfCode = $this->countTotalLines($filename);

        return $comments;
    }

    private function shouldAnalyzeMissingDocBlocks(): bool
    {
        return
            empty($this->configDTO->only)
            || in_array($this->missingDocBlock->getName(), $this->configDTO->only, true);
    }

    /**
     * @param array<mixed> $tokens
     * @return CommentDTO[]
     */
    private function getCommentsFromFile(array $tokens, string $filename): array
    {
        $comments = [];
        foreach ($tokens as $token) {
            if (!is_array($token) || !in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }
            $commentType = $this->commentFactory->classifyComment($token[1]);
            if ($commentType) {
                $comments[] =
                    new CommentDTO(
                        $commentType->getName(),
                        $commentType->getColor(),
                        $filename,
                        $token[2],
                        $token[1],
                    );
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

    /**
     * @param CommentDTO[] $comments
     * @param CommentStatisticsDTO[] $preparedStatistics
     * @param int $filesAnalyzed
     * @return OutputDTO
     */
    private function createOutputDTO(
        array $comments,
        array $preparedStatistics,
        int $filesAnalyzed
    ): OutputDTO {
        $comToLoc = $this->metrics->prepareComToLoc($preparedStatistics, $this->totalLinesOfCode);
        $cds = $this->metrics->prepareCDS($this->metrics->calculateCDS($preparedStatistics));
        $exceedThreshold = $this->checkThresholdsExceeded();
        $this->metrics->stopPerformanceMonitoring();
        $performanceMetrics = $this->metrics->getPerformanceMetrics();

        return new OutputDTO(
            $filesAnalyzed,
            $preparedStatistics,
            $comments,
            $performanceMetrics,
            $comToLoc,
            $cds,
            $exceedThreshold
        );
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

        return new CommentStatisticsDTO('', $type, $stat['lines'], '', $stat['count']);
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
