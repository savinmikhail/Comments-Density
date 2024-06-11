<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Commands;

use Mockery;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Commands\AnalyzeFileCommand;
use SavinMikhail\CommentsDensity\CommentDensity;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\FileAnalyzer;
use SavinMikhail\CommentsDensity\Metrics\CDS;
use SavinMikhail\CommentsDensity\Metrics\ComToLoc;
use SavinMikhail\CommentsDensity\Metrics\Metrics;
use SavinMikhail\CommentsDensity\Metrics\PerformanceMonitor;
use SavinMikhail\CommentsDensity\MissingDocBlockAnalyzer;
use SavinMikhail\CommentsDensity\Reporters\ReporterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class AnalyzeFileCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecute(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);
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

        $commentDensity = new CommentDensity(
            $configDto,
            $commentFactory,
            $fileAnalyzer,
            $this->createMock(ReporterInterface::class),
            $missingDocBlock,
            $metricsFacade
        );

        $command = Mockery::mock(AnalyzeFileCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Properly initialize the command class
        $command->__construct();
        $command->shouldReceive('getProjectRoot')->andReturn('/path/to/project');
        $command->shouldReceive('parseConfigFile')->andReturn($configArray);
        $command->shouldReceive('analyze')->andReturnUsing(function (CommentDensity $analyzer, string $file, OutputInterface $output) use ($commentDensity) {
            return $commentDensity->analyzeFile($file) ? Command::FAILURE : Command::SUCCESS;
        });

        // Add necessary expectations for InputInterface
        $input->shouldReceive('getArgument')->with('file')->andReturn('/path/to/file');
        $input->shouldReceive('getArgument')->with('command')->andReturn('analyze:file');
        $input->shouldReceive('bind')->andReturnNull();
        $input->shouldReceive('validate')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(false);
        $input->shouldReceive('hasArgument')->andReturn(true);

        // Add necessary expectations for OutputInterface
        $output->shouldReceive('writeln')->andReturnNull();

        $result = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteWithThresholdExceeded(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);
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

        $commentDensity = new CommentDensity(
            $configDto,
            $commentFactory,
            $fileAnalyzer,
            $this->createMock(ReporterInterface::class),
            $missingDocBlock,
            $metricsFacade
        );

        $commentDensityMock = Mockery::mock($commentDensity)->makePartial();
        $commentDensityMock->shouldReceive('analyzeFile')->andReturn(true);

        $command = Mockery::mock(AnalyzeFileCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Properly initialize the command class
        $command->__construct();
        $command->shouldReceive('getProjectRoot')->andReturn('/path/to/project');
        $command->shouldReceive('parseConfigFile')->andReturn($configArray);
        $command->shouldReceive('analyze')->andReturnUsing(function (CommentDensity $analyzer, string $file, OutputInterface $output) use ($commentDensityMock) {
            return $commentDensityMock->analyzeFile($file) ? Command::FAILURE : Command::SUCCESS;
        });

        // Add necessary expectations for InputInterface
        $input->shouldReceive('getArgument')->with('file')->andReturn('/path/to/file');
        $input->shouldReceive('getArgument')->with('command')->andReturn('analyze:file');
        $input->shouldReceive('bind')->andReturnNull();
        $input->shouldReceive('validate')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(false);
        $input->shouldReceive('hasArgument')->andReturn(true);

        // Add necessary expectations for OutputInterface
        $output->shouldReceive('writeln')->andReturnNull();

        $result = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $result);
    }
}
