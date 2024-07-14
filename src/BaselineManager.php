<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use SavinMikhail\CommentsDensity\Database\SQLiteDatabaseManager;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;

use function file_exists;
use function touch;

final class BaselineManager
{
    private static ?self $instance = null;
    private Connection $connection;

    private function __construct()
    {
        $this->init();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init(): void
    {
        $databaseFile = __DIR__ . '/../../comments_density.sqlite';
        if (!file_exists($databaseFile)) {
            touch($databaseFile);
        }
        $dbManager = new SQLiteDatabaseManager($databaseFile);
        $this->connection = $dbManager->getConnection();
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

    private function isUniqueConstraintViolation(Exception $exception): bool
    {
        return str_contains($exception->getMessage(), 'UNIQUE constraint failed');
    }

    public function getAllComments(): array
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('comments')
            ->executeQuery();

        return $query->fetchAllAssociative();
    }
}
