<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline\Storage;

use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;

interface BaselineStorageInterface
{
    /**
     * @param CommentDTO[] $comments
     * @return void
     */
    public function setComments(array $comments): void;

    /**
     * @param CommentDTO[] $comments
     * @return CommentDTO[]
     */
    public function filterComments(array $comments): array;
}
