<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Service\DBConnectionSetterInterface;
use PDO;

class PostgresDBStructureExtractor implements DBStructureExtractorInterface, DBDriverNameInterface, DBConnectionSetterInterface
{
    private PDO $conn;

    public function extractTables()
    {
        dump($this->conn);
        die;
    }

    public function extractTable(string $name)
    {
        // TODO: Implement extractTable() method.
    }

    public function getDBDriverName(): string
    {
        return 'pgsql';
    }

    public function setDBConnection(PDO $connection): void
    {
        $this->conn = $connection;
    }
}
