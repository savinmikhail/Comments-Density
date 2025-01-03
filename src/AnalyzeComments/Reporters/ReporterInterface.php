<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Reporters;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\OutputDTO;

interface ReporterInterface
{
    public function report(OutputDTO $dto): void;
}
