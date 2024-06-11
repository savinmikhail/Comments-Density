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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

use function dirname;

class AnalyzeFileCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('analyze:file')
            ->setDescription('Analyzes the comment density in a single PHP file.')
            ->addArgument('file', InputArgument::REQUIRED, 'The PHP file to analyze')
            ->setHelp('This command allows you to analyze the comments in a single PHP file.');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $this->getProjectRoot() . '/comments_density.yaml';

        $yamlParser = new Parser();
        $config = $yamlParser->parseFile($configFile);

        $thresholds = $config['thresholds'];
        $outputConfig = $config['output'] ?? [];
        $file = $input->getArgument('file');
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
        $analyzer = new CommentDensity(
            $configDto,
            $thresholds,
            $fileAnalyzer,
            $outputConfig,
            $cds,
            new ComToLoc($configDto->thresholds),
            $missingDocBlock
        );

        $limitExceeded = $analyzer->analyzeFile($file);

        if ($limitExceeded) {
            $output->writeln('<error>Comment thresholds were exceeded!</error>');
            $output->writeln('<info>To skip commit checks, add -n or --no-verify flag to commit command</info>');
            return Command::FAILURE;
        }
        $output->writeln('<info>Comment thresholds are passed!</info>');
        return Command::SUCCESS;
    }

    private function getProjectRoot(): string
    {
        return dirname(__DIR__, 4);
    }
}
