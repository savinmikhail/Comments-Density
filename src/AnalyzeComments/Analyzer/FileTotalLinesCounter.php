<?php

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use SplFileInfo;

class FileTotalLinesCounter
{
    public function run(SplFileInfo $file): int
    {
        return count(file($file->getRealPath()));
    }
}