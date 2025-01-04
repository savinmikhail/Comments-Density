<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline\Commands;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\ConfigLoader;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
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
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('baseline')
            ->setDescription('Generate a baseline of comments to ignore them in the future')
            ->setHelp('This command allows you to ignore old tech debt and start this quality check from this point');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->configLoader->getProjectRoot() . '/baseline.php';

        $this->storage->init($path);

        $configDto = $this->configLoader->getConfigDto();

        $analyzer = $this->analyzerFactory->getAnalyzer($configDto, $this->storage);
        $report = $analyzer->analyze();

        $this->storage->setComments($report->comments); // todo create some baseline reporter

        $output->writeln('<info>Baseline generated successfully!</info>');

        return Command::SUCCESS;
    }
}
