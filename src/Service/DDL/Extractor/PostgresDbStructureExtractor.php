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
use App\Model\DDL\IndexStructure;
use App\Model\DDL\TableStructure;
use App\Service\DbConnectionSetterInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver;
use Doctrine\DBAL\Exception;
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
     * @var Connection|null
     */
    private ?Connection $conn = null;

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
        $tableStructure = [];
        foreach ($this->getTableList() as $table) {
            $tableStructure[] = $this->getTableStructure($table);
        }

        return $tableStructure;
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
    public function getDbDriver(): string
    {
        return Driver::class;
    }

    /**
     * @inheritDoc
     */
    public function setDbConnection(Connection $connection): void
    {
        $this->conn = $connection;
    }

    /**
     * Return list of db tables.
     *
     * @return string[]
     * @throws ConnectionNotInjected
     * @throws Exception
     */
    private function getTableList(): array
    {
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema= ?";
        $dataTables = $this->getConnection()->fetchAllAssociative($sql, ['public']);

        $tables = [];
        foreach ($dataTables as $tableName) {
            $tables[] = $tableName['table_name'];
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
     * @param string $tableName
     *
     * @return array<IndexStructure>
     * @throws ConnectionNotInjected
     * @throws Exception
     * @throws ExceptionInterface
     */
    private function getTableIndexes(string $tableName): array
    {
        $sql = "
            SELECT tablename, indexname, indexdef
            FROM pg_indexes
            WHERE tablename = :table_name";
        $data = $this->getConnection()->fetchAllAssociative($sql, ['table_name' => $tableName]);

        $indexes = [];
        foreach ($data as $row) {
            $indexes = $this->denormalizer->denormalize($row, IndexStructure::class, 'array');
        }

        return $indexes;
    }

    /**
     * Extract and return table fields structures.
     *
     * @return DdlQueryPartInterface[]
     * @throws ExceptionInterface
     * @throws ConnectionNotInjected
     * @throws Exception
     * @throws ExceptionInterface
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

        $stmt = $this->getConnection()->fetchAllAssociative($sql, ['table_name' => $tableName]);

        $fields = [];
        foreach ($stmt as $row) {
            $fields[] = $this->denormalizer->denormalize($row, FieldStructure::class, 'array');
        }

        return $fields;
    }

    /**
     * Return db connection.
     *
     * @throws ConnectionNotInjected
     */
    private function getConnection(): Connection
    {
        if ($this->conn === null) {
            throw ConnectionNotInjected::create();
        }

        return $this->conn;
    }
}
