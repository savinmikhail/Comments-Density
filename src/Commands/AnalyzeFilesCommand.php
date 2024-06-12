<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

use function dirname;

class AnalyzeFilesCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('analyze:files')
            ->setDescription('Analyzes the comment density in multiple PHP files.')
            ->addArgument('files', InputArgument::IS_ARRAY, 'The PHP files to analyze')
            ->setHelp('This command allows you to analyze the comments in multiple PHP files.');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $this->getProjectRoot() . '/comments_density.yaml';
        $config = $this->parseConfigFile($configFile);

        $thresholds = $config['thresholds'];
        $outputConfig = $config['output'] ?? [];
        $files = $input->getArgument('files');
        $missingDocBlock = new MissingDocBlockAnalyzer();
        $configDto = new ConfigDTO(
            $thresholds,
            [],
            $outputConfig
        );
        $commentFactory = new CommentFactory();
        $cds = new CDS($configDto->thresholds, $commentFactory);
        $fileAnalyzer = new FileAnalyzer(
            $output,
            $missingDocBlock,
            $cds,
            $commentFactory
        );
        $metrics = new Metrics(
            $cds,
            new ComToLoc($configDto->thresholds),
            new PerformanceMonitor(),
        );
        $analyzer = new CommentDensity(
            $configDto,
            $commentFactory,
            $fileAnalyzer,
            new ConsoleReporter($output),
            $missingDocBlock,
            $metrics
        );

        return $this->analyzeFiles($analyzer, $files, $output);
    }

    protected function analyzeFiles(CommentDensity $analyzer, array $files, OutputInterface $output): int
    {
        $limitExceeded = $analyzer->analyzeFiles($files);

        if ($limitExceeded) {
            $output->writeln('<error>Comment thresholds were exceeded!</error>');
            $output->writeln('<info>To skip commit checks, add -n or --no-verify flag to commit command</info>');
            return Command::FAILURE;
        }
        $output->writeln('<info>Comment thresholds are passed!</info>');
        return Command::SUCCESS;
    }

    protected function getProjectRoot(): string
    {
        return dirname(__DIR__, 5);
    }

    protected function parseConfigFile(string $configFile): array
    {
        $yamlParser = new Parser();
        return $yamlParser->parseFile($configFile);
    }
}
