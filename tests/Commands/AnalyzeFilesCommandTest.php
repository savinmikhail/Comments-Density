<?php

declare(strict_types=1);

namespace Commands;

use FilesystemIterator;
use Generator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\Analyzer\Analyzer;
use SavinMikhail\CommentsDensity\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\PerformanceMetricsDTO;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use SavinMikhail\CommentsDensity\Commands\AnalyzeFilesCommand;
use SavinMikhail\CommentsDensity\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\Config\DTO\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\Config\DTO\OutputDTO as InputOutputDTO;
use SavinMikhail\CommentsDensity\Reporters\ConsoleReporter;
use SavinMikhail\CommentsDensity\Reporters\ReporterFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use function ini_set;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;

final class AnalyzeFilesCommandTest extends TestCase
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

        $command = $this->getMockBuilder(AnalyzeFilesCommand::class)
            ->onlyMethods(['getConfigDto'])
            ->getMock();

        $command->method('getConfigDto')->willReturn($configDto);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('analyze:files'));

        $result = $commandTester->execute(['files' => [__FILE__]], ['interactive' => false]);

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

        $analyzer = $this->createMock(Analyzer::class);
        $outputDTO = new OutputDTO(
            0,
            [],
            [],
            new PerformanceMetricsDTO(0, 0),
            new ComToLocDTO(0, 'red'),
            new CdsDTO(0, 'red'),
            true,
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

        $command = $this->getMockBuilder(AnalyzeFilesCommand::class)
            ->onlyMethods(['getConfigDto'])
            ->getMock();

        $command->method('getConfigDto')->willReturn($configDto);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('analyze:files'));

        $result = $commandTester->execute(['files' => [__FILE__]], ['interactive' => false]);

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
