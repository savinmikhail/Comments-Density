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
            $pathParts = explode(DIRECTORY_SEPARATOR, ltrim($comment->file, DIRECTORY_SEPARATOR));
            $this->addCommentToTree($this->baselineData, $pathParts, $comment);
        }
        file_put_contents($this->path, "<?php return " . var_export($this->baselineData, true) . ";");
    }

    private function addCommentToTree(array &$tree, array $pathParts, $comment): void
    {
        $currentPart = array_shift($pathParts);
        if (!isset($tree[$currentPart])) {
            $tree[$currentPart] = [];
        }
        if (empty($pathParts)) {
            $tree[$currentPart][$comment->line] = [
                'comment' => $comment->content,
                'type' => $comment->commentType,
            ];
        } else {
            $this->addCommentToTree($tree[$currentPart], $pathParts, $comment);
        }
    }

    public function filterComments(array $comments): array
    {
        $filteredComments = array_filter($comments, function ($comment): bool {
            $pathParts = explode(DIRECTORY_SEPARATOR, ltrim($comment['file'], DIRECTORY_SEPARATOR));
            return !$this->commentExistsInTree($this->baselineData, $pathParts, $comment['line']);
        });

        return array_values($filteredComments);
    }

    private function commentExistsInTree(array $tree, array $pathParts, int $line): bool
    {
        $currentPart = array_shift($pathParts);
        if (!isset($tree[$currentPart])) {
            return false;
        }
        if (empty($pathParts)) {
            return isset($tree[$currentPart][$line]);
        } else {
            return $this->commentExistsInTree($tree[$currentPart], $pathParts, $line);
        }
    }
}
