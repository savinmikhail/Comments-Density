<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\File;

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SplFileInfo;

final readonly class FileContentExtractor
{
    public function __construct(
        private SplFileInfo $file,
        private Config $configDTO,
    ) {}

    public function getContent(): string
    {
        return file_get_contents($this->file->getRealPath());
    }
}
