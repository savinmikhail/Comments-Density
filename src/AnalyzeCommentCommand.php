<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class AnalyzeCommentCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('analyze:comments'); // Explicitly setting the command name here.

        $this
        ->setDescription('Analyzes the comment density in a given file.')
        ->setHelp('This command allows you to analyze the comments in a PHP file.')
        ->addArgument('filename', InputArgument::REQUIRED, 'The filename to analyze');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument('filename');
        $analyzer = new CommentDensity($filename, $output);
        $analyzer->printStatistics();

        return Command::SUCCESS;
    }
}

