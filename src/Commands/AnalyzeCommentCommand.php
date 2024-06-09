<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use SavinMikhail\CommentsDensity\CommentDensity;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Input\InputDTO;
use SavinMikhail\CommentsDensity\FileAnalyzer;
use SavinMikhail\CommentsDensity\MissingDocBlockAnalyzer;
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

        $yamlParser = new Parser();
        $config = $yamlParser->parseFile($configFile);

        $directories = array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['directories'] ?? [$this->getProjectRoot() . '/' . $config['directory']]
        );
        $exclude = array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['exclude'] ?? [$this->getProjectRoot() . '/' . $config['exclude']]
        );
        $thresholds = $config['thresholds'] ?? [];
        $outputConfig = $config['output'] ?? [];

        $inputDTO = new ConfigDTO(
            $thresholds,
            $exclude,
            $outputConfig
        );

        $commentFactory = new CommentFactory();
        $analyzer = new CommentDensity(
            $output,
            $inputDTO,
            $commentFactory,
            new FileAnalyzer(
                $output,
                new MissingDocBlockAnalyzer(),
                new StatisticCalculator($commentFactory),
                $commentFactory
            )
        );
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
