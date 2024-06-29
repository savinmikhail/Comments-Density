<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use Generator;
use SavinMikhail\CommentsDensity\AnalyzerFactory;
use SavinMikhail\CommentsDensity\CommentDensity;
use SavinMikhail\CommentsDensity\ConfigLoader;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\Reporters\ReporterInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{
    protected function getConfigDto(): ConfigDTO
    {
        $configLoader = new ConfigLoader();
        return $configLoader->getConfigDto();
    }

    protected function analyze(CommentDensity $analyzer, Generator $files, OutputInterface $output): int
    {
        $limitExceeded = $analyzer->analyze($files);

        if ($limitExceeded) {
            $output->writeln('<error>Comment thresholds were exceeded!</error>');
            return SymfonyCommand::FAILURE;
        }
        $output->writeln('<info>Comment thresholds are passed!</info>');
        return SymfonyCommand::SUCCESS;
    }

    protected function getAnalyzer(
        AnalyzerFactory $factory,
        ConfigDTO $configDto,
        OutputInterface $output,
        ReporterInterface $reporter
    ): CommentDensity {
        return $factory->getAnalyzer($configDto, $output, $reporter);
    }
}
