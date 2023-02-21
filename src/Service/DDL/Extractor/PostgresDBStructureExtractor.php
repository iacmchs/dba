<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Exception\Service\DDL\Extractor\ConnectionNotInjected;
use App\Model\DDL\DDLQueryPartInterface;
use App\Model\DDL\FieldStructure;
use App\Model\DDL\TableStructure;
use App\Service\DBConnectionSetterInterface;
use PDO;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * The class extract tables metadata from a postgresql db
 */
class PostgresDBStructureExtractor implements
    DBStructureExtractorInterface,
    DBDriverNameInterface,
    DBConnectionSetterInterface
{
    private ?PDO $conn = null;

    private DenormalizerInterface $denormalizer;

    public function __construct(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * @inheritDoc
     * @throws ConnectionNotInjected
     * @throws ExceptionInterface
     */
    public function extractTables(): array
    {
        $tablesStructures = [];
        foreach ($this->getTablesList() as $table) {
            $tablesStructures[] = $this->getTableStructure($table);
        }

        return $tablesStructures;
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
    public function getDBDriverName(): string
    {
        return 'pgsql';
    }

    /**
     * @inheritDoc
     */
    public function setDBConnection(PDO $connection): void
    {
        $this->conn = $connection;
    }

    /**
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
     * @throws ConnectionNotInjected
     * @throws ExceptionInterface
     */
    private function getTableStructure(string $tableName): TableStructure
    {
        return new TableStructure($tableName, $this->getTableFieldsStructure($tableName));
    }

    /**
     * @return DDLQueryPartInterface[]
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

        dump($fields);

        return $fields;
    }

    /**
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
