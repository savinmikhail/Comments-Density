<?php

namespace SavinMikhail\CommentsDensity\Comments;

class FixMeComment extends Comment implements CommentTypeInterface
{
    public function getPattern(): string
    {
        return '/(?:\/\/|#|\/\*|\*|<!--).*?\bfixme\b.*/i';
    }

    public function getColor(): string
    {
        return 'yellow';
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
        return -0.3;
    }

    public function getAttitude(): string
    {
        return 'unwanted';
    }

    public function getName(): string
    {
        return 'fixme';
    }
}
