<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use SavinMikhail\CommentsDensity\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class BaselineCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('generate:baseline')
            ->setDescription('Generate a baseline of comments to ignore them in the future')
            ->setHelp('This command allows you to ignore old tech debt and start this quality check from this point');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = __DIR__ . '/../../baseline.php';

        $storage = new TreePhpBaselineStorage();
        $storage->init($path);

        $configDto = $this->getConfigDto();

        $files = $this->getFilesFromDirectories($configDto->directories);
        $analyzerFactory = new AnalyzerFactory();

        $analyzer = $analyzerFactory->getAnalyzer($configDto, $output, $storage);
        $outputDTO = $analyzer->analyze($files);

        $storage->setComments($outputDTO->comments);

        $output->writeln('<info>Baseline generated successfully!</info>');

        return SymfonyCommand::SUCCESS;
    }
}
