<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Database;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

final readonly class SQLiteDatabaseManager
{
    private Connection $connection;

    public function __construct(string $databaseFile)
    {
        $connectionParams = [
            'driver' => 'sqlite3',
            'path' => $databaseFile,
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

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
