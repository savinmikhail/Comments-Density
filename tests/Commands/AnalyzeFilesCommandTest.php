<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Commands;

use Mockery;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Commands\AnalyzeFilesCommand;
use SavinMikhail\CommentsDensity\CommentDensity;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\FileAnalyzer;
use SavinMikhail\CommentsDensity\Metrics\CDS;
use SavinMikhail\CommentsDensity\Metrics\ComToLoc;
use SavinMikhail\CommentsDensity\Metrics\Metrics;
use SavinMikhail\CommentsDensity\Metrics\PerformanceMonitor;
use SavinMikhail\CommentsDensity\MissingDocBlockAnalyzer;
use SavinMikhail\CommentsDensity\Reporters\ConsoleReporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Yaml\Parser;

class AnalyzeFilesCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/comment_density_test';
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob("$this->tempDir/*"));
        rmdir($this->tempDir);
        Mockery::close();
    }

    private function createTempFile(string $filename, string $content): string
    {
        $filePath = $this->tempDir . '/' . $filename;
        file_put_contents($filePath, $content);
        return $filePath;
    }

    public function testExecute(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);
        $formatter = Mockery::mock(OutputFormatterInterface::class);
        $yamlParser = Mockery::mock(Parser::class);

        $configArray = [
            'thresholds' => [],
            'output' => ['type' => 'console']
        ];

        $yamlParser->shouldReceive('parseFile')->andReturn($configArray);

        // Create actual instances
        $configDto = new ConfigDTO([], [], ['type' => 'console']);
        $commentFactory = new CommentFactory();
        $missingDocBlock = new MissingDocBlockAnalyzer();
        $cds = new CDS($configDto->thresholds, $commentFactory);
        $comToLoc = new ComToLoc($configDto->thresholds);
        $performanceMonitor = new PerformanceMonitor();
        $fileAnalyzer = new FileAnalyzer($output, $missingDocBlock, $cds, $commentFactory);
        $metricsFacade = new Metrics($cds, $comToLoc, $performanceMonitor);

        $reporter = new ConsoleReporter($output);

        $commentDensity = new CommentDensity(
            $configDto,
            $commentFactory,
            $fileAnalyzer,
            $reporter,
            $missingDocBlock,
            $metricsFacade
        );

        $command = Mockery::mock(AnalyzeFilesCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Properly initialize the command class
        $command->__construct();
        $command->shouldReceive('getProjectRoot')->andReturn($this->tempDir);
        $command->shouldReceive('parseConfigFile')->andReturn($configArray);
        $command->shouldReceive('analyze')->andReturnUsing(function (CommentDensity $analyzer, array $files, OutputInterface $output) use ($commentDensity) {
            return $commentDensity->analyzeFiles($files) ? Command::FAILURE : Command::SUCCESS;
        });

        // Add necessary expectations for InputInterface
        $input->shouldReceive('getArgument')->with('files')->andReturn([$this->createTempFile('file1.php', '<?php // test'), $this->createTempFile('file2.php', '<?php // test')]);
        $input->shouldReceive('getArgument')->with('command')->andReturn('analyze:files');

        $input->shouldReceive('bind')->andReturnNull();
        $input->shouldReceive('validate')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(false);
        $input->shouldReceive('hasArgument')->andReturn(true);

        // Add necessary expectations for OutputInterface
        $output->shouldReceive('writeln')->andReturnNull();
        $output->shouldReceive('getFormatter')->andReturn($formatter);

        // Add necessary expectations for OutputFormatterInterface
        $formatter->shouldReceive('isDecorated')->andReturn(false);
        $formatter->shouldReceive('setDecorated')->andReturnNull();
        $formatter->shouldReceive('format')->andReturnUsing(function ($text) { return $text; });

        $result = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteWithThresholdExceeded(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);
        $formatter = Mockery::mock(OutputFormatterInterface::class);
        $yamlParser = Mockery::mock(Parser::class);

        $configArray = [
            'thresholds' => [],
            'output' => ['type' => 'console']
        ];

        $yamlParser->shouldReceive('parseFile')->andReturn($configArray);

        // Create actual instances
        $configDto = new ConfigDTO([], [], ['type' => 'console']);
        $commentFactory = new CommentFactory();
        $missingDocBlock = new MissingDocBlockAnalyzer();
        $cds = new CDS($configDto->thresholds, $commentFactory);
        $comToLoc = new ComToLoc($configDto->thresholds);
        $performanceMonitor = new PerformanceMonitor();
        $fileAnalyzer = new FileAnalyzer($output, $missingDocBlock, $cds, $commentFactory);
        $metricsFacade = new Metrics($cds, $comToLoc, $performanceMonitor);

        $reporter = new ConsoleReporter($output);

        $commentDensity = new CommentDensity(
            $configDto,
            $commentFactory,
            $fileAnalyzer,
            $reporter,
            $missingDocBlock,
            $metricsFacade
        );

        $commentDensityMock = Mockery::mock($commentDensity)->makePartial();
        $commentDensityMock->shouldReceive('analyzeFiles')->andReturn(true);

        $command = Mockery::mock(AnalyzeFilesCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Properly initialize the command class
        $command->__construct();
        $command->shouldReceive('getProjectRoot')->andReturn($this->tempDir);
        $command->shouldReceive('parseConfigFile')->andReturn($configArray);
        $command->shouldReceive('analyze')->andReturnUsing(function (CommentDensity $analyzer, array $files, OutputInterface $output) use ($commentDensityMock) {
            return $commentDensityMock->analyzeFiles($files) ? Command::FAILURE : Command::SUCCESS;
        });

        // Add necessary expectations for InputInterface
        $input->shouldReceive('getArgument')->with('files')->andReturn([$this->createTempFile('file1.php', '<?php // test'), $this->createTempFile('file2.php', '<?php // test')]);
        $input->shouldReceive('getArgument')->with('command')->andReturn('analyze:files');

        $input->shouldReceive('bind')->andReturnNull();
        $input->shouldReceive('validate')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(false);
        $input->shouldReceive('hasArgument')->andReturn(true);

        // Add necessary expectations for OutputInterface
        $output->shouldReceive('writeln')->andReturnNull();
        $output->shouldReceive('getFormatter')->andReturn($formatter);

        // Add necessary expectations for OutputFormatterInterface
        $formatter->shouldReceive('isDecorated')->andReturn(false);
        $formatter->shouldReceive('setDecorated')->andReturnNull();
        $formatter->shouldReceive('format')->andReturnUsing(function ($text) { return $text; });

        $result = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $result);
    }
}
