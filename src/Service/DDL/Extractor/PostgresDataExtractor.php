<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Configuration\ConfigurationManagerInterface;
use App\Exception\Service\DDL\Extractor\ConfigurationManagerNotInjected;
use App\Exception\Service\DDL\Extractor\ConnectionNotInjected;
use App\Service\DbConnectionSetterInterface;
use App\Service\DDL\DbDataExtractorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver;
use Symfony\Component\Filesystem\Filesystem;

/**
 * PostgreSQL data extractor.
 */
class PostgresDataExtractor implements
    DbDataExtractorInterface,
    DbDriverNameInterface,
    DbConnectionSetterInterface,
    ConfigurationManagerSetterInterface
{
    /**
     * DB connection.
     *
     * @var Connection|null
     */
    private ?Connection $connection;

    private ?ConfigurationManagerInterface $configurationManager;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    /**
     * @inheritDoc
     */
    public function getDbDriver(): string
    {
        return Driver::class;
    }

    /**
     * Returns db connection.
     *
     * @return Connection
     *
     * @throws ConnectionNotInjected
     */
    private function getConnection(): Connection
    {
        if (!$this->connection) {
            throw ConnectionNotInjected::create();
        }

        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function setDbConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Returns configuration manager.
     *
     * @return ConfigurationManagerInterface
     *
     * @throws ConfigurationManagerNotInjected
     */
    private function getConfigurationManager(): ConfigurationManagerInterface
    {
        if (!$this->configurationManager) {
            throw ConfigurationManagerNotInjected::create();
        }

        return $this->configurationManager;
    }

    /**
     * @inheritDoc
     */
    public function setConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @inheritDoc
     */
    public function dumpTable(string $tableName, string $dir, array $tableConfig = [], string $fileNamePrefix = '10'): void
    {
        if (!$tableConfig) {
            $tableConfig = $this->getConfigurationManager()->getTableConfig($tableName);
        }

        $filePath = $dir . '/' . $this->getNewTableFileName($tableName, $fileNamePrefix);
        $sql = "INSERT INTO $tableName VALUES" . PHP_EOL;

        $query = $this->getDataSelectQuery($tableConfig);
        $needSaveAfterEachRow = $tableConfig['export_method'] === 'row';
        $hasResult = false;

        foreach ($this->getConnection()->iterateNumeric($query) as $row) {
            $hasResult = true;

            // Export previous row to file.
            if ($needSaveAfterEachRow) {
                $this->filesystem->appendToFile($filePath, $sql);
                $sql = '';
            }

            // Prepare current row for export.
            $values = '';
            foreach ($row as $key => $item) {
                if (null === $item) {
                    $values .= 'null';
                } elseif (is_bool($item)) {
                    $values .= $item ? 'true' : 'false';
                } elseif (is_numeric($item)) {
                    $values .= $item;
                } elseif (is_resource($item)) {
                    $item = stream_get_contents($item);
                    $values .= "'" . pg_escape_bytea($item) . "'";
                } else {
                    $values .= "'" . pg_escape_string((string) $item) . "'";
                }

                if (array_key_last($row) !== $key) {
                    $values .= ',';
                }
            }

            $sql .= "($values)," . PHP_EOL;
        }

        // Export last row (or all rows) to file.
        if ($hasResult) {
            $sql = rtrim(rtrim($sql), ',') . ';';
            if ($needSaveAfterEachRow) {
                $this->filesystem->appendToFile($filePath, $sql);
            }
            else {
                $this->filesystem->dumpFile($filePath, $sql);
            }
        }
    }

    /**
     * Returns select query to get data from table.
     *
     * @param array $tableConfig
     *   Table config.
     *
     * @return string
     *   SQL query to get data from table.
     */
    private function getDataSelectQuery(array $tableConfig): string
    {
        $query = 'SELECT * FROM ' . $tableConfig['table'];

        $where = [];
        foreach ($tableConfig['where'] as $fieldName => $condition) {
            if (!is_array($condition)) {
                $condition = [$condition, '='];
            }

            switch ($condition[1]) {
                case 'expression':
                    $where[] = $condition[0];
                    break;

                default:
                    if (!str_starts_with((string) $condition[0], '(')) {
                        $condition[0] = "'" . $condition[0] . "'";
                    }

                    $where[] = "$fieldName {$condition[1]} {$condition[0]}";
            };
        }

        if ($tableConfig['get'] < 1) {
            $where[] = 'RANDOM() < ' . $tableConfig['get'];
        }

        if ($where) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function canTableBeDumped(string $tableName, array $tableConfig = []): bool
    {
        return $this->getConfigurationManager()->canTableBeDumped($tableName, $tableConfig);
    }

    /**
     * Get new Table file name.
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
