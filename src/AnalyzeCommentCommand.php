<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class AnalyzeCommentCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('analyze:comments')
            ->setDescription('Analyzes the comment density in files within a directory.')
            ->setHelp('This command allows you to analyze the comments in PHP files within a specified directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $this->getProjectRoot() . '/comments_density.yaml';
        $config = Yaml::parseFile($configFile);

        $directory = $this->getProjectRoot() . '/' . $config['directory'];
        $thresholds = $config['thresholds'];

        $files = glob("$directory/*.php");

        foreach ($files as $filename) {
            $output->writeln("<info>Analyzing $filename</info>");
            $analyzer = new CommentDensity($filename, $output, $thresholds);
            $analyzer->printStatistics();
        }

        return Command::SUCCESS;
    }

    private function getProjectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}

