<?php

namespace SavinMikhail\CommentsDensity\Comments;

class LicenseComment extends Comment implements CommentTypeInterface
{
    public function getPattern(): string
    {
        return '/\/\*\*.*?\b(?:license|copyright|permission)\b.*?\*\//is';
    }

    public function getColor(): string
    {
       return 'white';
    }

    public function getStatColor(int $count, array $thresholds): string
    {
        if (! isset($thresholds[$this->getName()])) {
            return 'white';
        }
        if ($count >= $thresholds[$this->getName()]) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    public function getWeight(): float
    {
        return 0;
    }

    public function getAttitude(): string
    {
        return 'neutral';
    }

    public function getName(): string
    {
        return 'license';
    }
}