<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\File;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SplFileInfo;

final readonly class FileFinder
{
    public function __construct(
        private Config $config,
    ) {}

    /**
     * @return SplFileInfo[]
     */
    public function __invoke(): iterable
    {
        foreach ($this->config->directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if ($this->shouldSkipFile($file)) {
                    continue;
                }
                yield $file;
            }
        }
    }

    private function shouldSkipFile(SplFileInfo $file): bool
    {
        return
            $this->isInWhitelist($file->getRealPath())
            || $file->getSize() === 0
            || !$this->isPhpFile($file)
            || !$file->isReadable();
    }

    private function isPhpFile(SplFileInfo $file): bool
    {
        return $file->isFile() && $file->getExtension() === 'php';
    }

    private function isInWhitelist(string $filePath): bool
    {
        foreach ($this->config->exclude as $whitelistedDir) {
            if (str_contains($filePath, $whitelistedDir)) {
                return true;
            }
        }

        return false;
    }
}
