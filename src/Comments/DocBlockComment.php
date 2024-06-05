<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

final class DocBlockComment extends Comment implements CommentTypeInterface
{
    public function getPattern(): string
    {
        return '/\/\*\*(?!.*\b(?:license|copyright|permission)\b).+?\*\//is';
    }

    public function getColor(): string
    {
        return 'green';
    }

    public function getStatColor(int $count, array $thresholds): string
    {
        if ($count >= $thresholds[$this->getName()]) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    public function getWeight(): float
    {
        return 1;
    }

    public function getAttitude(): string
    {
        return 'good';
    }

    public function getName(): string
    {
        return 'docBlock';
    }
}
