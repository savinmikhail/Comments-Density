<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Commands;

use FilesystemIterator;
use Generator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\Analyzer;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\PerformanceMetricsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Commands\AnalyzeCommentCommand;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\OutputDTO as InputOutputDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Reporters\ConsoleReporter;
use SavinMikhail\CommentsDensity\AnalyzeComments\Reporters\ReporterFactory;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use SplFileInfo;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class AnalyzeCommentCommandTest extends TestCase
{
    private string $tempCacheDir;

    protected function setUp(): void
    {
        ini_set('memory_limit', '256M'); // Increase memory limit for tests

        // Create a temporary cache directory for each test
        $this->tempCacheDir = sys_get_temp_dir() . '/cache_' . uniqid();
        mkdir($this->tempCacheDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        // Clean up the temporary cache directory after each test
        $this->removeDirectory($this->tempCacheDir);
    }

    public function testExecuteSuccess(): void
    {
        $docblockConfigDTO = new MissingDocblockConfigDTO(
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
        );

        $configDto = $this->createMock(ConfigDTO::class);
        $configDto->docblockConfigDTO = $docblockConfigDTO;
        $configDto->only = [];
        $configDto->thresholds = [];
        $configDto->cacheDir = $this->tempCacheDir;
        $configDto->directories = [__DIR__];
        $configDto->exclude = [];
        $configDto->output = new InputOutputDTO('console', '');
        $configDto->useBaseline = false;

        $baselineStorage = $this->createMock(TreePhpBaselineStorage::class);
        $baselineStorage->method('filterComments')->willReturn([]);

        $analyzer = $this->createMock(Analyzer::class);
        $outputDTO = new OutputDTO(
            0,
            [],
            [],
            new PerformanceMetricsDTO(0, 0),
            new ComToLocDTO(0, 'red'),
            new CdsDTO(0, 'red'),
            false,
        );
        $analyzer
            ->method('analyze')
            ->willReturn($outputDTO);

        $analyzerFactory = $this->createMock(AnalyzerFactory::class);
        $analyzerFactory->method('getAnalyzer')->willReturn($analyzer);

        $reporter = $this->createMock(ConsoleReporter::class);
        $reporter
            ->method('report')
            ->with(self::equalTo($outputDTO));

        $reporterFactory = $this->createMock(ReporterFactory::class);
        $reporterFactory->method('createReporter')->willReturn($reporter);

        $command = $this->getMockBuilder(AnalyzeCommentCommand::class)
            ->onlyMethods(['getConfigDto', 'getFilesFromDirectories'])
            ->getMock();

        $command->method('getConfigDto')->willReturn($configDto);
        $command
            ->method('getFilesFromDirectories')
            ->willReturn($this->createGenerator([new SplFileInfo(__FILE__)]));

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('analyze:comments'));

        $result = $commandTester->execute([], ['interactive' => false]);

        self::assertSame(0, $result);
        self::assertStringContainsString('Comment thresholds are passed!', $commandTester->getDisplay());
    }

    public function testExecuteFailure(): void
    {
        $docblockConfigDTO = new MissingDocblockConfigDTO(
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
        );
        $configDto = $this->createMock(ConfigDTO::class);
        $configDto->docblockConfigDTO = $docblockConfigDTO;
        $configDto->only = [];
        $configDto->thresholds = ['CDS' => 1];
        $configDto->cacheDir = $this->tempCacheDir;
        $configDto->directories = [__DIR__];
        $configDto->output = new InputOutputDTO('console', '');
        $configDto->exclude = [];
        $configDto->useBaseline = false;

        $baselineStorage = $this->createMock(TreePhpBaselineStorage::class);
        $baselineStorage->method('filterComments')->willReturn([]);

        $outputDTO = new OutputDTO(
            0,
            [],
            [],
            new PerformanceMetricsDTO(0, 0),
            new ComToLocDTO(0, 'red'),
            new CdsDTO(0, 'red'),
            true,
        );

        $analyzer = $this->createMock(Analyzer::class);
        $analyzer
            ->method('analyze')
            ->willReturn($outputDTO);

        $analyzerFactory = $this->createMock(AnalyzerFactory::class);
        $analyzerFactory->method('getAnalyzer')->willReturn($analyzer);

        $reporter = $this->createMock(ConsoleReporter::class);
        $reporter
            ->method('report')
            ->with(self::equalTo($outputDTO));

        $reporterFactory = $this->createMock(ReporterFactory::class);
        $reporterFactory->method('createReporter')->willReturn($reporter);

        $command = $this->getMockBuilder(AnalyzeCommentCommand::class)
            ->onlyMethods(['getConfigDto', 'getFilesFromDirectories'])
            ->getMock();

        $command->method('getConfigDto')->willReturn($configDto);
        $command->method('getFilesFromDirectories')->willReturn($this->createGenerator([new SplFileInfo(__FILE__)]));

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('analyze:comments'));

        $result = $commandTester->execute([], ['interactive' => false]);

        self::assertSame(1, $result);
        self::assertStringContainsString('Comment thresholds were exceeded!', $commandTester->getDisplay());
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($dir);
    }

    private function createGenerator(array $items): Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }
}
