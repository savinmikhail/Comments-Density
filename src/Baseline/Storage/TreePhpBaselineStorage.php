<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline\Storage;

final class TreePhpBaselineStorage implements BaselineStorageInterface
{
    private string $path;
    private array $baselineData = [];

    public function init(string $path): void
    {
        $this->path = $path;
        if (!file_exists($path)) {
            file_put_contents($path, "<?php return [];");
        }
        $this->baselineData = include $path;
    }

    public function setComments(array $comments): void
    {
        foreach ($comments as $comment) {
            $this->baselineData[$comment->file][$comment->line] = [
                'comment' => $comment->content,
                'type' => $comment->commentType,
            ];
        }
        file_put_contents($this->path, "<?php return " . var_export($this->baselineData, true) . ";");
    }

    public function filterComments(array $comments): array
    {
        return array_filter(
            $comments,
            fn(array $comment) => !isset($this->baselineData[$comment['file']][$comment['line']])
        );
    }
}
