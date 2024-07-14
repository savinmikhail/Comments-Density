<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use Generator;
use SavinMikhail\CommentsDensity\AnalyzerFactory;
use SavinMikhail\CommentsDensity\Reporters\ConsoleReporter;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SplFileInfo;

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

        $filePaths = $input->getArgument('files');

        $files = $this->generateFiles($filePaths);

        $analyzerFactory = new AnalyzerFactory();
        $analyzer = $analyzerFactory->getAnalyzer($configDto, $output);
        $outputDTO = $analyzer->analyze($files);

        $reporter = new ConsoleReporter($output);
        $reporter->report($outputDTO);

        if ($outputDTO->exceedThreshold) {
            $output->writeln('<error>Comment thresholds were exceeded!</error>');
            return SymfonyCommand::FAILURE;
        }

        $output->writeln('<info>Comment thresholds are passed!</info>');
        return SymfonyCommand::SUCCESS;
    }

    protected function generateFiles(array $filePaths): Generator
    {
        foreach ($filePaths as $filePath) {
            yield new SplFileInfo($filePath);
        }
    }
}
