<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use Generator;
use SavinMikhail\CommentsDensity\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use SavinMikhail\CommentsDensity\Reporters\ConsoleReporter;
use SplFileInfo;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AnalyzeFilesCommand extends Command
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
        $path = __DIR__ . '/../../baseline.php';

        $storage = new TreePhpBaselineStorage();
        $storage->init($path);

        $configDto = $this->getConfigDto();

        $filePaths = $input->getArgument('files');

        $files = $this->generateFiles($filePaths);

        $analyzerFactory = new AnalyzerFactory();
        $analyzer = $analyzerFactory->getAnalyzer($configDto, $output, $storage);
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

    /**
     * @param string[] $filePaths
     */
    protected function generateFiles(array $filePaths): Generator
    {
        foreach ($filePaths as $filePath) {
            yield new SplFileInfo($filePath);
        }
    }
}
