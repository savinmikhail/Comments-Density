<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use SavinMikhail\CommentsDensity\Reporters\ReporterFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $configDto = $this->getConfigDto();

        $files = $this->getFilesFromDirectories($this->getDirectories($configDto));
        $reporter = (new ReporterFactory())->createReporter($output, $configDto);

        $analyzer = $this->getAnalyzer($configDto, $output, $reporter);

        return $this->analyze($analyzer, $files, $output);
    }
}
