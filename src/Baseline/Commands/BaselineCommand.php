<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline\Commands;

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

final class BaselineCommand extends Command
{
    public function __construct(
        private readonly ConfigLoader $configLoader = new ConfigLoader(),
        private readonly TreePhpBaselineStorage $storage = new TreePhpBaselineStorage(),
        private readonly AnalyzerFactory $analyzerFactory = new AnalyzerFactory(),
        ?string $name = null,
    )
    {
        parent::__construct($name);
    }
    protected function configure(): void
    {
        $this->setName('generate:baseline')
            ->setDescription('Generate a baseline of comments to ignore them in the future')
            ->setHelp('This command allows you to ignore old tech debt and start this quality check from this point');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->configLoader->getProjectRoot() . '/baseline.php';

        $this->storage->init($path);

        $configDto = $this->configLoader->getConfigDto();
        $files = $this->getFilesFromDirectories($configDto->directories);

        $analyzer = $this->analyzerFactory->getAnalyzer($configDto, $output, $this->storage);
        $outputDTO = $analyzer->analyze($files);

        $this->storage->setComments($outputDTO->comments); // todo create some baseline reporter

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
