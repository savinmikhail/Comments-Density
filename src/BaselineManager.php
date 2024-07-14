<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;

final class BaselineManager
{
    public function __construct(
        private Connection $connection
    ) {
    }

    /**
     * @throws Exception
     */
    public function set(OutputDTO $outputDTO): void
    {
        foreach ($outputDTO->comments as $comment) {
            try {
                $this->connection->insert('comments', [
                    'file_path' => $comment->file,
                    'line_number' => $comment->line,
                    'comment' => $comment->content,
                    'type' => $comment->commentType,
                ]);
            } catch (Exception $e) {
                if ($this->isUniqueConstraintViolation($e)) {
                    $this->connection->update('comments', [
                        'comment' => $comment->content,
                        'type' => $comment->commentType,
                    ], [
                        'file_path' => $comment->file,
                        'line_number' => $comment->line,
                    ]);
                    continue;
                }
                throw $e;
            }
        }
    }

    private function isUniqueConstraintViolation(Exception $e): bool
    {
        return str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }

    public function getAllComments(): array
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('comments')
            ->executeQuery();

        return $query->fetchAllAssociative();
    }

    public function getCommentsByFilePath(string $filePath): array
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('comments')
            ->where('file_path = :file_path')
            ->setParameter('file_path', $filePath)
            ->executeQuery();

        return $query->fetchAllAssociative();
    }
}
