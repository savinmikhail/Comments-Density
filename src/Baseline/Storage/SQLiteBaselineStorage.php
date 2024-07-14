<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Baseline\Storage;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

final class SQLiteBaselineStorage implements BaselineStorageInterface
{
    private Connection $connection;

    public function init(string $path): void
    {
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'path' => $path,
        ];
        $this->connection = DriverManager::getConnection($connectionParams);
        $this->initializeDatabase();
    }

    private function initializeDatabase(): void
    {
        $schema = $this->connection->createSchemaManager();
        $tables = $schema->listTables();

        if (!in_array('comments', array_map(fn($table) => $table->getName(), $tables))) {
            $this->connection->executeStatement('
                CREATE TABLE comments (
                    file_path TEXT NOT NULL,
                    line_number INTEGER NOT NULL,
                    comment TEXT NOT NULL,
                    type TEXT NOT NULL,
                    PRIMARY KEY (file_path, line_number)
                )
            ');
        }
    }

    public function setComments(array $comments): void
    {
        foreach ($comments as $comment) {
            $this->connection->insert('comments', [
                'file_path' => $comment->file,
                'line_number' => $comment->line,
                'comment' => $comment->content,
                'type' => $comment->commentType,
            ]);
        }
    }

    public function filterComments(array $comments): array
    {
        $filteredComments = [];
        foreach ($comments as $comment) {
            $query = $this->connection->createQueryBuilder()
                ->select('*')
                ->from('comments')
                ->where('file_path = :file_path')
                ->andWhere('line_number = :line_number')
                ->setParameter('file_path', $comment->file)
                ->setParameter('line_number', $comment->line)
                ->executeQuery();

            if (!$query->fetchAssociative()) {
                $filteredComments[] = $comment;
            }
        }
        return $filteredComments;
    }
}
