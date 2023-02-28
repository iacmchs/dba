<?php

/**
 * @file
 * Implementation of the DbStructureExtractorInterface for a Postgresql.
 */

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Exception\Service\DDL\Extractor\ConnectionNotInjected;
use App\Model\DDL\DdlQueryPartInterface;
use App\Model\DDL\FieldStructure;
use App\Model\DDL\TableStructure;
use App\Service\DbConnectionSetterInterface;
use PDO;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PostgresDbStructureExtractor implements
    DbStructureExtractorInterface,
    DbDriverNameInterface,
    DbConnectionSetterInterface
{
    /**
     * DB connection.
     *
     * @var PDO|null
     */
    private ?PDO $conn = null;

    /**
     * Create db structure extractor for Postgresql.
     *
     * @param DenormalizerInterface $denormalizer
     */
    public function __construct(
        private readonly DenormalizerInterface $denormalizer
    )
    {
    }

    /**
     * @inheritDoc
     * @throws ConnectionNotInjected
     * @throws ExceptionInterface
     */
    public function extractTables(): array
    {
        $tableStructures = [];
        foreach ($this->getTablesList() as $table) {
            $tableStructures[] = $this->getTableStructure($table);
        }

        return $tableStructures;
    }

    /**
     * @inheritDoc
     * @throws ExceptionInterface
     * @throws ConnectionNotInjected
     */
    public function extractTable(string $name): TableStructure
    {
        return $this->getTableStructure($name);
    }

    /**
     * @inheritDoc
     */
    public function getDbDriverName(): string
    {
        return 'pgsql';
    }

    /**
     * @inheritDoc
     */
    public function setDbConnection(PDO $connection): void
    {
        $this->conn = $connection;
    }

    /**
     * Return list of db tables.
     *
     * @return string[]
     * @throws ConnectionNotInjected
     */
    private function getTablesList(): array
    {
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema='public'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tables[] = $row['table_name'];
        }

        /** @psalm-var string[] */
        return $tables;
    }

    /**
     * Extract and return db table structure by name.
     *
     * @throws ConnectionNotInjected
     * @throws ExceptionInterface
     */
    private function getTableStructure(string $tableName): TableStructure
    {
        return new TableStructure($tableName, $this->getTableFieldsStructure($tableName));
    }

    /**
     * Extract and return table fields structures.
     *
     * @return DdlQueryPartInterface[]
     * @throws ExceptionInterface
     * @throws ConnectionNotInjected
     */
    private function getTableFieldsStructure(string $tableName): array
    {
        $sql = "SELECT
                    column_name,
                    data_type,
                    is_nullable,
                    column_default,
                    character_maximum_length
                FROM
                    information_schema.columns
                WHERE
                    table_name = :table_name";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['table_name' => $tableName]);

        $fields = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fields[] = $this->denormalizer->denormalize($row, FieldStructure::class, 'array');
        }

        return $fields;
    }

    /**
     * Return db connection.
     *
     * @throws ConnectionNotInjected
     */
    private function getConnection(): PDO
    {
        if ($this->conn === null) {
            throw ConnectionNotInjected::create();
        }

        return $this->conn;
    }
}
