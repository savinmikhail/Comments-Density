<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

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

        $commentFactory = new CommentFactory();
        $analyzer = new CommentDensity(
            $output,
            $thresholds,
            [],
            $outputConfig,
            $commentFactory,
            new FileAnalyzer(
                $output,
                new MissingDocBlockAnalyzer(),
                new StatisticCalculator($commentFactory),
                $commentFactory
            )
        );

        $limitExceeded = $analyzer->analyzeFile($file);

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
