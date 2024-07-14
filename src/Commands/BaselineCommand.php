<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use SavinMikhail\CommentsDensity\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\Baseline\BaselineManager;
use SavinMikhail\CommentsDensity\Baseline\Storage\SimplePhpBaselineStorage;
use SavinMikhail\CommentsDensity\Baseline\Storage\SQLiteBaselineStorage;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class BaselineCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('generate:baseline')
            ->setDescription('Generate a baseline of comments to ignore them in the future')
            ->addOption('storage', 's', InputOption::VALUE_REQUIRED, 'The type of storage to use (sqlite, tree, simple)', 'sqlite')
            ->setHelp('This command allows you to ignore old tech debt and start this quality check from this point');
    }

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
        $analyzerFactory = new AnalyzerFactory();

        $analyzer = $analyzerFactory->getAnalyzer($configDto, $output, $baselineManager);
        $outputDTO = $analyzer->analyze($files);

        $baselineManager->setComments($outputDTO->comments);

        $output->writeln('<info>Baseline generated successfully!</info>');

        return SymfonyCommand::SUCCESS;
    }
}
