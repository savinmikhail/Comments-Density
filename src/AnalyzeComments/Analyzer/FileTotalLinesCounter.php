<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use SplFileInfo;

use function count;

final class FileTotalLinesCounter
{
    public function run(SplFileInfo $file): int
    {
        return count(file($file->getRealPath()));
    }
}
