<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use SavinMikhail\CommentsDensity\AnalyzerFactory;
use SavinMikhail\CommentsDensity\Reporters\ConsoleReporter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SplFileInfo;

use function array_map;

class AnalyzeFilesCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('analyze:files')
            ->setDescription('Analyzes the comment density in multiple PHP files.')
            ->addArgument('files', InputArgument::IS_ARRAY, 'The PHP files to analyze')
            ->setHelp('This command allows you to analyze the comments in multiple PHP files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configDto = $this->getConfigDto();

        $files = $input->getArgument('files');
        $files = array_map(function($filePath) {
            return new SplFileInfo($filePath);
        }, $files);

        $reporter = new ConsoleReporter($output);
        $analyzerFactory = new AnalyzerFactory();

        $analyzer = $this->getAnalyzer($analyzerFactory, $configDto, $output, $reporter);

        return $this->analyze($analyzer, $files, $output);
    }
}
