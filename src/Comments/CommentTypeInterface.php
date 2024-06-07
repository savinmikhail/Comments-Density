<?php

namespace SavinMikhail\CommentsDensity\Comments;

interface CommentTypeInterface
{
    public function getPattern(): string;
    public function getColor(): string;
    public function getStatColor(int $count, array $thresholds): string;
    public function getWeight(): float;
    public function getAttitude(): string;
    public function getName(): string;
    public function matchesPattern(string $token): bool;
}
