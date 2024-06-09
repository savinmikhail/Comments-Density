<?php

namespace SavinMikhail\CommentsDensity\Reporters;

use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;

interface ReporterInterface
{
    public function report(OutputDTO $dto): void;
}
