<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline\Storage;

interface BaselineStorageInterface
{
    public function init(string $path): void;
    public function setComments(array $comments): void;
    public function filterComments(array $comments): array;
}
