<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Commands;

use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\Config\ConfigLoader;
use SavinMikhail\CommentsDensity\Config\DTO\ConfigDTO;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

abstract class Command extends SymfonyCommand
{
    protected function getConfigDto(): ConfigDTO
    {
        return (new ConfigLoader())->getConfigDto();
    }

    /**
     * @param string[] $directories
     */
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
