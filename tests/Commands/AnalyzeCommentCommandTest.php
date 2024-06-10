<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Commands;

use Mockery;
use Symfony\Component\Console\Command\Command;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\CDS;
use SavinMikhail\CommentsDensity\CommentDensity;
use SavinMikhail\CommentsDensity\Commands\AnalyzeCommentCommand;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\ComToLoc;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\FileAnalyzer;
use SavinMikhail\CommentsDensity\MissingDocBlockAnalyzer;
use SavinMikhail\CommentsDensity\Reporters\ReporterFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class AnalyzeCommentCommandTest extends TestCase
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

        $yamlParser->shouldReceive('parseFile')->andReturn([
            'directories' => ['src'],
            'exclude' => ['tests'],
            'thresholds' => [],
            'output' => ['type' => 'console']
        ]);

        // Create actual instances
        $configDto = new ConfigDTO([], ['tests'], ['type' => 'console']);
        $commentFactory = new CommentFactory();
        $missingDocBlock = new MissingDocBlockAnalyzer();
        $fileAnalyzer = new FileAnalyzer($output, $missingDocBlock, new CDS($configDto->thresholds, $commentFactory), $commentFactory);
        $reporter = (new ReporterFactory())->createReporter($output, $configDto);

        $commentDensity = new CommentDensity($configDto, $commentFactory, $fileAnalyzer, $reporter, new CDS($configDto->thresholds, $commentFactory), new ComToLoc($configDto->thresholds), $missingDocBlock);

        // Create a partial mock of the actual instance
        $commentDensityMock = Mockery::mock($commentDensity)->makePartial();
        $commentDensityMock->shouldReceive('analyzeDirectories')->andReturn(false);

        $command = Mockery::mock(AnalyzeCommentCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Properly initialize the command class
        $command->__construct();
        $command->shouldReceive('getProjectRoot')->andReturn('/path/to/project');
        $command->shouldReceive('parseConfigFile')->andReturnUsing([$yamlParser, 'parseFile']);
        $command->shouldReceive('analyze')->andReturnUsing(function (CommentDensity $analyzer, array $directories, OutputInterface $output) use ($commentDensityMock) {
            return $commentDensityMock->analyzeDirectories($directories) ? Command::FAILURE : Command::SUCCESS;
        });

        // Add necessary expectations for InputInterface
        $input->shouldReceive('bind')->andReturnNull();
        $input->shouldReceive('validate')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(false);
        $input->shouldReceive('hasArgument')->andReturn(false);  // Add this line to mock hasArgument

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

        $yamlParser->shouldReceive('parseFile')->andReturn([
            'directories' => ['src'],
            'exclude' => ['tests'],
            'thresholds' => [],
            'output' => ['type' => 'console']
        ]);

        // Create actual instances
        $configDto = new ConfigDTO([], ['tests'], ['type' => 'console']);
        $commentFactory = new CommentFactory();
        $missingDocBlock = new MissingDocBlockAnalyzer();
        $fileAnalyzer = new FileAnalyzer($output, $missingDocBlock, new CDS($configDto->thresholds, $commentFactory), $commentFactory);
        $reporter = (new ReporterFactory())->createReporter($output, $configDto);

        // Create an instance of CommentDensity
        $commentDensity = new CommentDensity($configDto, $commentFactory, $fileAnalyzer, $reporter, new CDS($configDto->thresholds, $commentFactory), new ComToLoc($configDto->thresholds), $missingDocBlock);

        // Create a partial mock of the actual instance and set the analyzeDirectories method to return true
        $commentDensityMock = Mockery::mock($commentDensity)->makePartial();
        $commentDensityMock->shouldReceive('analyzeDirectories')->andReturn(true);

        $command = Mockery::mock(AnalyzeCommentCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Properly initialize the command class
        $command->__construct();
        $command->shouldReceive('getProjectRoot')->andReturn('/path/to/project');
        $command->shouldReceive('parseConfigFile')->andReturnUsing([$yamlParser, 'parseFile']);
        $command->shouldReceive('analyze')->andReturnUsing(function (CommentDensity $analyzer, array $directories, OutputInterface $output) use ($commentDensityMock) {
            return $commentDensityMock->analyzeDirectories($directories) ? Command::FAILURE : Command::SUCCESS;
        });

        // Add necessary expectations for InputInterface
        $input->shouldReceive('bind')->andReturnNull();
        $input->shouldReceive('validate')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(false);
        $input->shouldReceive('hasArgument')->andReturn(false);

        // Add necessary expectations for OutputInterface
        $output->shouldReceive('writeln')->andReturnNull();

        $result = $command->run($input, $output);

        $this->assertEquals(Command::FAILURE, $result);
    }
}
