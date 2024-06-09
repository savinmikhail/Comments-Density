<?php

namespace SavinMikhail\CommentsDensity\Reporters;

use SavinMikhail\CommentsDensity\DTO\OutputDTO;

interface ReporterInterface
{
    public function report(OutputDTO $dto): void;
}
