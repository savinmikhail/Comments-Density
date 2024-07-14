<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use SavinMikhail\CommentsDensity\AnalyzerFactory;
use SavinMikhail\CommentsDensity\BaselineManager;
use SavinMikhail\CommentsDensity\Database\SQLiteDatabaseManager;
use SavinMikhail\CommentsDensity\Reporters\ReporterFactory;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function file_exists;
use function touch;

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
        $baselineManager = new BaselineManager();

        $configDto = $this->getConfigDto();

        $files = $this->getFilesFromDirectories($configDto->directories);
        $analyzerFactory = new AnalyzerFactory();

        $analyzer = $analyzerFactory->getAnalyzer($configDto, $output);
        $outputDTO = $analyzer->analyze($files);

        $baselineManager->init()->set($outputDTO);

        $output->writeln('<info>Baseline generated successfully!</info>');

        return SymfonyCommand::SUCCESS;
    }
}