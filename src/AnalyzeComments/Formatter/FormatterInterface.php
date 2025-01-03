<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Formatter;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\Report;

interface FormatterInterface
{
    public function report(Report $dto): void;
}
