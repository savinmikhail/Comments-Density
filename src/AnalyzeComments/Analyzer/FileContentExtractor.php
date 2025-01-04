<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SplFileInfo;

final readonly class FileContentExtractor
{
    public function __construct(
        private SplFileInfo $file,
        private Config      $configDTO,
    ) {}

    public function getContent(): string
    {
        return file_get_contents($this->file->getRealPath());
    }

    public function shouldSkipFile(): bool
    {
        return
            $this->isInWhitelist($this->file->getRealPath())
            || $this->file->getSize() === 0
            || !$this->isPhpFile($this->file)
            || !$this->file->isReadable();
    }

    private function isPhpFile(SplFileInfo $file): bool
    {
        return $file->isFile() && $file->getExtension() === 'php';
    }

    private function isInWhitelist(string $filePath): bool
    {
        foreach ($this->configDTO->exclude as $whitelistedDir) {
            if (str_contains($filePath, $whitelistedDir)) {
                return true;
            }
        }

        return false;
    }
}
