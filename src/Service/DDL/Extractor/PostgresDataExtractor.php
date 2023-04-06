<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Configuration\ExportDbConfiguration;
use App\Exception\Service\DDL\Extractor\ConnectionNotInjected;
use App\Service\DbConnectionSetterInterface;
use App\Service\DDL\DbDataExtractorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver;
use Doctrine\DBAL\Exception;
use Symfony\Component\Filesystem\Filesystem;

/**
 * PostgreSQL data extractor.
 */
class PostgresDataExtractor implements
    DbDriverNameInterface,
    DbConnectionSetterInterface,
    DbDataConfigurationSetterInterface,
    DbDataExtractorInterface
{
    /**
     * @param Filesystem $filesystem
     */
    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    /**
     * DB connection.
     *
     * @var Connection|null
     */
    private ?Connection $connection = null;

    /**
     * Export DB configuration
     *
     * @var ExportDbConfiguration|null
     */
    private ?ExportDbConfiguration $configuration = null;

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
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     *
     * @param ExportDbConfiguration $configuration
     *
     * @return void
     */
    public function setConfiguration(ExportDbConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string      $table
     * @param string      $path
     * @param string|null $prefix
     *
     * @throws ConnectionNotInjected
     * @throws Exception
     */
    public function dumpTable(string $table, string $path, ?string $prefix = ''): void
    {
        $sql = "INSERT INTO $table VALUES";

        $sqlRandom = '';

        $percent = $this->getPercent($table);
        if ($percent && $percent < 1) {
            $sqlRandom = " WHERE RANDOM() < $percent";
        }

        $requestTable = "SELECT * FROM $table".$sqlRandom;
        $haveResult = false;
        foreach ($this->getConnection()->iterateNumeric($requestTable) as $row) {
            $haveResult = true;
            $result = '';
            foreach ($row as $key => $item) {
                if (null === $item) {
                    $result .= 'null';
                } elseif (is_bool($item)) {
                    $result .= $item ? 'true' : 'false';
                } elseif (is_int($item) || is_float($item)) {
                    $result .= $item;
                } else {
                    $result .= "'$item'";
                }

                if (array_key_last($row) !== $key) {
                    $result .= ',';
                }
            }

            $sql .= " ($result),";
        }

        if ($haveResult) {
            $sql = rtrim($sql, ',');
            $sql .= ';';
            $fileName = $this->getNewTableFileName($table, $prefix);
            $this->filesystem->dumpFile($path.'/'.$fileName, $sql);
        }
    }

    /**
     * Can be table dumped.
     *
     * @param string $table
     *
     * @return bool
     *
     * @throws Exception
     */
    public function canTableBeDumped(string $table): bool
    {
        return (bool) $this->getPercent($table);
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
        if ($this->connection === null) {
            throw ConnectionNotInjected::create();
        }

        return $this->connection;
    }

    /**
     * Get table dump percent.
     *
     * @param string $table
     *
     * @return float
     *   How much of table contents should be dumped - a value from 0 to 1,
     *   where 1 stands for 100%.
     *   If table is not listed in database.tables section of the config file
     *   then we assume that this table should be fully dumped (1 is returned).
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function getPercent(string $table): float
    {
        $database = $this->connection->getDatabase();
        $configTables = $this->configuration->getTables($database);
        $percent = NULL;

        foreach ($configTables as $configTableKey => $configTableValue) {
            if (is_numeric($configTableValue) && $configTableKey == $table) {
                $percent = (float) $configTableValue;
            }
            elseif (!empty($configTableValue['table']) && $configTableValue['table'] === $table) {
                $percent = (float) ($configTableValue['get'] ?? 0);
            }
            elseif (!empty($configTableValue['table_regex']) && preg_match($configTableValue['table_regex'], $table)) {
                $percent = (float) ($configTableValue['get'] ?? 0);
            }

            if ($percent) {
                break;
            }
        }

        return $percent ?? 1;
    }

    /**
     * Get new Table file name
     *
     * @param string $name
     * @param string $prefix
     *
     * @return string
     */
    private function getNewTableFileName(string $name, ?string $prefix = ''): string
    {
        $name = str_replace('"', '', $name);

        return ($prefix ? $prefix.'_' : '').$name.'.sql';
    }
}
