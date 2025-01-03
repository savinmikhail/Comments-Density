<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\ConfigLoader;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;
use SavinMikhail\CommentsDensity\AnalyzeComments\Formatter\ConsoleFormatter;
use SavinMikhail\CommentsDensity\AnalyzeComments\Formatter\FormatterFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Formatter\HtmlFormatter;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AnalyzeCommentCommand extends Command
{
    public function __construct(
        private readonly ConfigLoader           $configLoader = new ConfigLoader(),
        private readonly TreePhpBaselineStorage $storage = new TreePhpBaselineStorage(),
        private readonly AnalyzerFactory        $analyzerFactory = new AnalyzerFactory(),
        ?string                                 $name = null,
    )
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('analyze')
            ->setDescription('Analyzes the comment density in files within a directory.')
            ->setHelp('This command allows you to analyze the comments in PHP files within a specified directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->configLoader->getProjectRoot() . '/baseline.php';

        $this->storage->init($path);

        $configDto = $this->configLoader->getConfigDto();
        $files = $this->getFilesFromDirectories($configDto->directories);

        $formatters = ['console' => new ConsoleFormatter($output)];
        if ($configDto->output->type === 'html') {
            $formatters['html'] = new HtmlFormatter($configDto->output->file);
        }
        $formatter = $formatters[$configDto->output->type] ?? $formatters['console'];

        $analyzer = $this->analyzerFactory->getAnalyzer($configDto, $this->storage);

        $report = $analyzer->analyze($files);

        $formatter->report($report);

        if ($report->exceedThreshold) {
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
