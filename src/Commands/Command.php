<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

use function array_map;
use function dirname;

use const DIRECTORY_SEPARATOR;

abstract class Command extends SymfonyCommand
{
    protected const CONFIG_FILE = 'comments_density.yaml';

    protected function parseConfigFile(string $configFile): array
    {
        $yamlParser = new Parser();
        return $yamlParser->parseFile($configFile);
    }

    protected function getProjectRoot(): string
    {
        return dirname(__DIR__, 5);
    }

    protected function getConfig(): array
    {
        $configFile = $this->getProjectRoot() . DIRECTORY_SEPARATOR . self::CONFIG_FILE;
        return $this->parseConfigFile($configFile);
    }

    protected function getDirectories(ConfigDTO $config): array
    {
        return array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config->directories
        );
    }

    protected function getExcludes(array $config): array
    {
        return array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['exclude']
        );
    }

    protected function getFilesFromDirectories(array $directories): array
    {
        $files = [];
        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                $files[] = $file;
            }
        }
        return $files;
    }

    protected function analyze(CommentDensity $analyzer, array $files, OutputInterface $output): int
    {
        $limitExceeded = $analyzer->analyzeFiles($files);

        if ($limitExceeded) {
            $output->writeln('<error>Comment thresholds were exceeded!</error>');
            return SymfonyCommand::FAILURE;
        }
        $output->writeln('<info>Comment thresholds are passed!</info>');
        return SymfonyCommand::SUCCESS;
    }

    protected function getConfigDto(): ConfigDTO
    {
        $config = $this->getConfig();
        $exclude = $this->getExcludes($config);
        $thresholds = $config['thresholds'];
        $outputConfig = $config['output'];
        $directories = $config['directories'];

        return new ConfigDTO(
            $thresholds,
            $exclude,
            $outputConfig,
            $directories,
        );
    }

    protected function getAnalyzer(
        ConfigDTO $configDto,
        OutputInterface $output,
        ReporterInterface $reporter
    ): CommentDensity {
        $commentFactory = new CommentFactory();
        $missingDocBlock = new MissingDocBlockAnalyzer();
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
            new PerformanceMonitor()
        );

        return new CommentDensity(
            $configDto,
            $commentFactory,
            $fileAnalyzer,
            $reporter,
            $missingDocBlock,
            $metrics
        );
    }
}
