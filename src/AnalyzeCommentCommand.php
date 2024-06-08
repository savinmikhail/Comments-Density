<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use SavinMikhail\CommentsDensity\Comments\CommentFactory;
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

        $yamlParser = new Parser();
        $config = $yamlParser->parseFile($configFile);

        $directory = $this->getProjectRoot() . '/' . $config['directory'];
        $thresholds = $config['thresholds'];
        $outputConfig = $config['output'] ?? [];

        $commentFactory = new CommentFactory();
        $analyzer = new CommentDensity(
            $output,
            $thresholds,
            $outputConfig,
            new MissingDocBlockAnalyzer(),
            new StatisticCalculator($commentFactory),
            $commentFactory,
        );
        $limitExceeded = $analyzer->analyzeDirectory($directory);

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
