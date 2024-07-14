<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

    protected function getFilesFromDirectories(array $directories): Generator
    {
        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                yield $file;
            }
        }
    }
}
