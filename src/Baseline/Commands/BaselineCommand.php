<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline\Commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\ConfigLoader;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
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
        $configLoader = new ConfigLoader();
        $path = $configLoader->getProjectRoot() . '/baseline.php';

        $storage = new TreePhpBaselineStorage();

        $storage->init($path);

        $configDto = $configLoader->getConfigDto();
        $files = $this->getFilesFromDirectories($configDto->directories);

        $analyzerFactory = new AnalyzerFactory();

        $analyzer = $analyzerFactory->getAnalyzer($configDto, $output, $storage);
        $outputDTO = $analyzer->analyze($files);

        $storage->setComments($outputDTO->comments);

        $output->writeln('<info>Baseline generated successfully!</info>');

        return Command::SUCCESS;
    }

    /**
     * @param string[] $directories
     * @return SplFileInfo[]
     */
    protected function getFilesFromDirectories(array $directories): iterable
    {
        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                yield $file;
            }
        }
    }
}
