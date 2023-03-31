<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Exception\Service\DDL\Extractor\ConnectionNotInjected;
use App\Model\DDL\DdlQueryPartInterface;
use App\Model\DDL\FieldStructure;
use App\Model\DDL\TableStructure;
use App\Service\DbConnectionSetterInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver;
use Doctrine\DBAL\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Implementation of the DbStructureExtractorInterface for a PostgresQL.
 */
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
     * @param string                $databaseDumpFolder
     * @param string                $pgDump
     * @param DenormalizerInterface $denormalizer
     * @param Filesystem            $filesystem
     */
    public function __construct(private readonly string $databaseDumpFolder, private readonly string $pgDump, private readonly DenormalizerInterface $denormalizer, private readonly Filesystem $filesystem)
    {
    }

    /**
     * @inheritDoc
     *
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
     *
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
     * @inheritDoc
     *
     * @return void
     *
     * @throws ConnectionNotInjected
     * @throws Exception
     */
    public function dumpStructure(): void
    {
        $database = $this->getConnection()->getDatabase();
        $params = $this->getConnection()->getParams();
        $command = [
            $this->pgDump,
            $database,
            '-U '.$params['user'],
            '-h '.$params['host'],
            '-p '.$params['port'],
            '-s',
        ];

        $folderName = $this->getNewStructureFolderName($database);
        $generateFile = $this->getNewStructureFileName($database);
        $folderPath = $this->getStructureFolderPath($folderName);

        $commandLine = implode(' ', $command);
        $commandLine .= ' > '.$folderPath.'/'.$generateFile;

        $this->createStructureFolder($folderPath);

        Process::fromShellCommandline($commandLine)->run();
    }

    /**
     * Return list of db tables.
     *
     * @return string[]
     *
     * @throws ConnectionNotInjected
     * @throws Exception
     */
    private function getTableList(): array
    {
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema= ?";

        $tables = [];
        foreach ($this->getConnection()->iterateAssociative($sql, ['public']) as $row) {
            $tables[] = $row['table_name'];
        }

        return $tables;
    }

    /**
     * Extract and return db table structure by name.
     *
     * @param string $tableName
     *
     * @return TableStructure
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
     * @param string $tableName
     *
     * @return DdlQueryPartInterface[]
     *
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

        $fields = [];
        foreach ($this->getConnection()->iterateAssociative($sql, ['table_name' => $tableName]) as $row) {
            $fields[] = $this->denormalizer->denormalize($row, FieldStructure::class, 'array');
        }

        return $fields;
    }

    /**
     * Return db connection.
     *
     * @return Connection
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

    /**
     * Create structure folder.
     *
     * @param string $path
     *
     * @return void
     */
    private function createStructureFolder(string $path): void
    {
        $this->filesystem->mkdir($path);
    }

    /**
     * Get new structure folder name.
     *
     * @param string $name
     *
     * @return string
     */
    private function getNewStructureFolderName(string $name): string
    {
        return $name.'_'.date('Ymd_His');
    }

    /**
     * Get new structure file name.
     *
     * @param string $name
     *
     * @return string
     */
    private function getNewStructureFileName(string $name): string
    {
        return '00_'.$name.'_structure.sql';
    }

    /**
     * Get structure folder path.
     *
     * @param string $folderName
     *
     * @return string
     */
    private function getStructureFolderPath(string $folderName): string
    {
        return $this->databaseDumpFolder.'/'.$folderName;
    }
}
