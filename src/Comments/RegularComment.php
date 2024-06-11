<?php

namespace SavinMikhail\CommentsDensity\Comments;

class RegularComment extends Comment implements CommentTypeInterface
{
    public function getPattern(): string
    {
        // phpcs:ignore Generic.Files.LineLength.TooLong
        return '/(#(?!.*\b(?:todo|fixme)\b:?).*?$)|(\/\/(?!.*\b(?:todo|fixme)\b:?).*?$)|\/\*(?!\*)(?!.*\b(?:todo|fixme)\b:?).*?\*\//ms';
    }

    public function getColor(): string
    {
        return 'red';
    }

    public function getStatColor(int $count, array $thresholds): string
    {
        if (! isset($thresholds[$this->getName()])) {
            return 'white';
        }
        if ($count <= $thresholds[$this->getName()]) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    public function getWeight(): float
    {
        return -1;
    }

    public function getAttitude(): string
    {
        return 'bad';
    }

    public function getName(): string
    {
        return 'regular';
    }
}
