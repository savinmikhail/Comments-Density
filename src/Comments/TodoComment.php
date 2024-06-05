<?php

namespace SavinMikhail\CommentsDensity\Comments;

class TodoComment extends Comment implements CommentTypeInterface
{

    public function getPattern(): string
    {
        return '/(?:\/\/|#|\/\*|\*|<!--).*?\btodo\b.*/i';
    }

    public function getColor(): string
    {
        return 'yellow';
    }

    public function getStatColor(int $count, array $thresholds): string
    {
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
        return 'todo';
    }
}