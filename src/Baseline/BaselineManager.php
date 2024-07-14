<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline;

use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;

final class BaselineManager
{
    private BaselineStorageInterface $storage;

    public function __construct(BaselineStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function init(string $path): self
    {
        $this->storage->init($path);
        return $this;
    }

    public function setComments(array $comments): void
    {
        $this->storage->setComments($comments);
    }

    public function filterComments(array $comments): array
    {
        return $this->storage->filterComments($comments);
    }
}
