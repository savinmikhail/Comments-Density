<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\File;

use SplFileInfo;

final readonly class FileContentExtractor
{
    public function __construct(
        private SplFileInfo $file,
    ) {}

    public function getContent(): string
    {
        return file_get_contents($this->file->getRealPath());
    }
}
