<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\ConfigLoader;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;
use SavinMikhail\CommentsDensity\AnalyzeComments\Reporters\ReporterFactory;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AnalyzeCommentCommand extends Command
{
    public function __construct(
        private readonly ConfigLoader $configLoader = new ConfigLoader(),
        private readonly TreePhpBaselineStorage $storage = new TreePhpBaselineStorage(),
        private readonly ReporterFactory $reporterFactory = new ReporterFactory(),
        private readonly AnalyzerFactory $analyzerFactory = new AnalyzerFactory(),
        ?string $name = null,
    )
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('analyze:comments')
            ->setDescription('Analyzes the comment density in files within a directory.')
            ->setHelp('This command allows you to analyze the comments in PHP files within a specified directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->configLoader->getProjectRoot() . '/baseline.php';

        $this->storage->init($path);

        $configDto = $this->configLoader->getConfigDto();
        $files = $this->getFilesFromDirectories($configDto->directories);

        $reporter = $this->reporterFactory->createReporter($output, $configDto);

        $analyzer = $this->analyzerFactory->getAnalyzer($configDto, $output, $this->storage);

        $outputDTO = $analyzer->analyze($files);

        $reporter->report($outputDTO);

        if ($outputDTO->exceedThreshold) {
            $output->writeln('<error>Comment thresholds were exceeded!</error>');

            return Command::FAILURE;
        }
        $output->writeln('<info>Comment thresholds are passed!</info>');

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
