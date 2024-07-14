<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline\Storage;

final class SimplePhpBaselineStorage implements BaselineStorageInterface
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
        // Clear the file content to ensure it's empty before adding new comments
        unlink($this->path);
        $this->init($this->path);

        // Read existing data
        $this->baselineData = include $this->path;

        // Open the file for appending
        $fileHandle = fopen($this->path, 'c+');

        if ($fileHandle === false) {
            throw new \RuntimeException("Unable to open file: " . $this->path);
        }

        // Lock the file
        if (flock($fileHandle, LOCK_EX)) {
            // Move to the end of the file before the closing bracket
            fseek($fileHandle, -2, SEEK_END);

            foreach ($comments as $comment) {
                $key = $comment->file . ':' . $comment->line;
                $data = [
                    'comment' => $comment->content,
                    'type' => $comment->commentType,
                ];

                if (!isset($this->baselineData[$key])) {
                    // Append the new comment to the file
                    $entry = var_export($key, true) . " => " . var_export($data, true) . ",\n";
                    fwrite($fileHandle, $entry);
                    $this->baselineData[$key] = $data;
                }
            }

            // Close the PHP array
            fwrite($fileHandle, "];");
            flock($fileHandle, LOCK_UN);
        }

        fclose($fileHandle);
    }

    public function filterComments(array $comments): array
    {
        return array_filter(
            $comments,
            fn(array $comment) => !isset($this->baselineData[$comment['file'] . ':' . $comment['line']])
        );
    }
}
