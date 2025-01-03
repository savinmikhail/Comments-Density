<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use SplFileInfo;

use function count;

final readonly class FileTotalLinesCounter
{
    public function __construct(
        private SplFileInfo $file,
    ) {}

    public function __invoke(): int
    {
        return count(file($this->file->getRealPath()));
    }
}
