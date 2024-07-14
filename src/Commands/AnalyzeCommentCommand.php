<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use SavinMikhail\CommentsDensity\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\Baseline\BaselineManager;
use SavinMikhail\CommentsDensity\Baseline\Storage\SimplePhpBaselineStorage;
use SavinMikhail\CommentsDensity\Baseline\Storage\SQLiteBaselineStorage;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use SavinMikhail\CommentsDensity\Reporters\ReporterFactory;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeCommentCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('analyze:comments')
            ->setDescription('Analyzes the comment density in files within a directory.')
            ->addOption('storage', 's', InputOption::VALUE_REQUIRED, 'The type of storage to use (sqlite, tree, simple)', 'sqlite')
            ->setHelp('This command allows you to analyze the comments in PHP files within a specified directory.');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storageType = $input->getOption('storage');
        $path = __DIR__ . '/../../baseline';

        $storage = match ($storageType) {
            'tree' => new TreePhpBaselineStorage(),
            'simple' => new SimplePhpBaselineStorage(),
            default => new SQLiteBaselineStorage(),
        };
        $extension = match ($storageType) {
            'tree', 'simple' => 'php',
            default => 'sqlite',
        };

        $baselineManager = (new BaselineManager($storage))->init($path . '.' . $extension);

        $configDto = $this->getConfigDto();

        $files = $this->getFilesFromDirectories($configDto->directories);
        $reporter = (new ReporterFactory())->createReporter($output, $configDto);
        $analyzerFactory = new AnalyzerFactory();

        $analyzer = $analyzerFactory->getAnalyzer($configDto, $output, $baselineManager);

        $outputDTO = $analyzer->analyze($files);

        $reporter->report($outputDTO);

        if ($outputDTO->exceedThreshold) {
            $output->writeln('<error>Comment thresholds were exceeded!</error>');
            return SymfonyCommand::FAILURE;
        }
        $output->writeln('<info>Comment thresholds are passed!</info>');
        return SymfonyCommand::SUCCESS;
    }
}
