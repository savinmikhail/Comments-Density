<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline\Storage;

use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use const DIRECTORY_SEPARATOR;

final class TreePhpBaselineStorage implements BaselineStorageInterface
{
    private string $path;

    /**
     * @var array<mixed>
     */
    private array $baselineData = [];

    public function init(string $path): void
    {
        $this->path = $path;
        if (!file_exists($path)) {
            file_put_contents($path, '<?php return [];');
        }
        $this->baselineData = include $path;
    }

    /**
     * @param CommentDTO[] $comments
     */
    public function setComments(array $comments): void
    {
        foreach ($comments as $comment) {
            $pathParts = explode(DIRECTORY_SEPARATOR, ltrim($comment->file, DIRECTORY_SEPARATOR));
            $this->addCommentToTree($this->baselineData, $pathParts, $comment);
        }
        file_put_contents($this->path, '<?php return ' . var_export($this->baselineData, true) . ';');
    }

    /**
     * @param CommentDTO[] $comments
     * @return CommentDTO[]
     */
    public function filterComments(array $comments): array
    {
        $filteredComments = array_filter($comments, function (CommentDTO $comment): bool {
            $pathParts = explode(DIRECTORY_SEPARATOR, ltrim($comment->file, DIRECTORY_SEPARATOR));

            return !$this->commentExistsInTree($this->baselineData, $pathParts, $comment->line);
        });

        return array_values($filteredComments);
    }

    /**
     * @param array<mixed> $tree
     * @param string[] $pathParts
     */
    private function addCommentToTree(array &$tree, array $pathParts, CommentDTO $comment): void
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

            return;
        }
        $this->addCommentToTree($tree[$currentPart], $pathParts, $comment);
    }

    /**
     * @param array<mixed> $tree
     * @param string[] $pathParts
     */
    private function commentExistsInTree(array $tree, array $pathParts, int $line): bool
    {
        $currentPart = array_shift($pathParts);

        if (!isset($tree[$currentPart])) {
            return false;
        }

        if (empty($pathParts)) {
            return isset($tree[$currentPart][$line]);
        }

        return $this->commentExistsInTree($tree[$currentPart], $pathParts, $line);
    }
}
