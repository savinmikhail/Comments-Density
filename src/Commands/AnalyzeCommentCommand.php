<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use SavinMikhail\CommentsDensity\CDS;
use SavinMikhail\CommentsDensity\CommentDensity;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\ComToLoc;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\FileAnalyzer;
use SavinMikhail\CommentsDensity\MissingDocBlockAnalyzer;
use SavinMikhail\CommentsDensity\Reporters\ConsoleReporter;
use SavinMikhail\CommentsDensity\Reporters\HtmlReporter;
use SavinMikhail\CommentsDensity\Reporters\ReporterInterface;
use SavinMikhail\CommentsDensity\StatisticCalculator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class AnalyzeCommentCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('analyze:comments')
            ->setDescription('Analyzes the comment density in files within a directory.')
            ->setHelp('This command allows you to analyze the comments in PHP files within a specified directory.');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $this->getProjectRoot() . '/comments_density.yaml';
        $config = $this->parseConfigFile($configFile);

        $directories = $this->getDirectories($config);
        $exclude = $this->getExcludes($config);
        $thresholds = $config['thresholds'] ?? [];
        $outputConfig = $config['output'] ?? [];

        $configDto = new ConfigDTO(
            $thresholds,
            $exclude,
            $outputConfig
        );

        $reporter = $this->getReporter($output, $configDto);
        $commentFactory = new CommentFactory();
        $missingDocBlock = new MissingDocBlockAnalyzer();
        $fileAnalyzer = new FileAnalyzer(
            $output,
            $missingDocBlock,
            new StatisticCalculator($commentFactory),
            $commentFactory
        );

        $analyzer = new CommentDensity(
            $configDto,
            $commentFactory,
            $fileAnalyzer,
            $reporter,
            new CDS($configDto->thresholds),
            new ComToLoc($configDto->thresholds),
            $missingDocBlock
        );

        return $this->analyze($analyzer, $directories, $output);
    }

    private function parseConfigFile(string $configFile): array
    {
        $yamlParser = new Parser();
        return $yamlParser->parseFile($configFile);
    }

    private function getDirectories(array $config): array
    {
        return array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['directories']
        );
    }

    private function getExcludes(array $config): array
    {
        return array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['exclude']
        );
    }

    private function getReporter(OutputInterface $output, ConfigDTO $configDto): ReporterInterface
    {
        if (!empty($configDto->outputConfig) && $configDto->outputConfig['type'] === 'html') {
            return new HtmlReporter(__DIR__ . '/../../../' . $configDto->outputConfig['file']);
        }
        return new ConsoleReporter($output);
    }

    private function analyze(CommentDensity $analyzer, array $directories, OutputInterface $output): int
    {
        $limitExceeded = $analyzer->analyzeDirectories($directories);

        if ($limitExceeded) {
            $output->writeln('<error>Comment thresholds were exceeded!</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Comment thresholds are passed!</info>');
        return Command::SUCCESS;
    }

    private function getProjectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
