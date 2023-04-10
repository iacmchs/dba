<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Configuration\ConfigurationManagerInterface;
use App\Exception\Service\DDL\Extractor\ConfigurationManagerNotInjectedException;
use App\Exception\Service\DDL\Extractor\ConnectionNotInjectedException;
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
     * The maximum number of rows that a single INSERT query may have.
     */
    const INSERT_ROWS_MAX = 300;

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
    public function dumpTable(string $tableName, string $dir, array $tableConfig = [], string $fileNamePrefix = '10'): void
    {
        if (!$tableConfig) {
            $tableConfig = $this->getConfigurationManager()->getTableConfig($tableName);
        }

        $filePath = $dir . '/' . $this->getNewTableFileName($tableName, $fileNamePrefix);
        $sql = $insertSql = "INSERT INTO $tableName VALUES" . PHP_EOL;

        $query = $this->getDataSelectQuery($tableConfig);
        $needSaveAfterEachRow = $tableConfig['export_method'] === 'row';
        $hasResult = false;
        $i = 0;

        foreach ($this->getConnection()->iterateNumeric($query) as $row) {
            $i++;
            $hasResult = true;

            // Export previous row to file.
            if ($needSaveAfterEachRow) {
                $this->filesystem->appendToFile($filePath, $sql);
                $sql = '';
            }

            // Split the insert query into parts to make it to have
            // 300 rows max to optimize performance on DB import.
            if ($i % self::INSERT_ROWS_MAX === 0) {
                $sql = $this->removeTrailingComma($sql) . ';' . PHP_EOL;
                $sql .= $insertSql;
            }

            // Prepare current row for export.
            $sql .= $this->getValuesQuery($row) . ',' . PHP_EOL;
        }

        // Export last row (or all rows) to file.
        if ($hasResult) {
            $sql = $this->removeTrailingComma($sql) . ';';
            if ($needSaveAfterEachRow) {
                $this->filesystem->appendToFile($filePath, $sql);
            } else {
                $this->filesystem->dumpFile($filePath, $sql);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function canTableBeDumped(string $tableName, array $tableConfig = []): bool
    {
        return $this->getConfigurationManager()->canTableBeDumped($tableName, $tableConfig);
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
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     */
    public function setConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Returns db connection.
     *
     * @return Connection
     *
     * @throws ConnectionNotInjectedException
     */
    private function getConnection(): Connection
    {
        if (!$this->connection) {
            throw ConnectionNotInjectedException::create();
        }

        return $this->connection;
    }

    /**
     * Returns configuration manager.
     *
     * @return ConfigurationManagerInterface
     *
     * @throws ConfigurationManagerNotInjectedException
     */
    private function getConfigurationManager(): ConfigurationManagerInterface
    {
        if (!$this->configurationManager) {
            throw ConfigurationManagerNotInjectedException::create();
        }

        return $this->configurationManager;
    }

    /**
     * Returns select query to get data from table.
     *
     * @param array $tableConfig
     *   Table config.
     *
     * @return string
     *   The SELECT sql query to get data from table.
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
     * Returns values for insert query.
     *
     * @param array $row
     *   Table row data to insert.
     *
     * @return string
     *   The part of the INSERT sql query that comes after this:
     *   INSERT INTO table VALUES ...
     */
    private function getValuesQuery(array $row): string
    {
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

        return "($values)";
    }

    /**
     * Removes trailing comma from the end of the query.
     *
     * @param string $query
     *   The sql query.
     *
     * @return string
     *   The sql query without trailing comma and spacing.
     */
    private function removeTrailingComma(string $query): string
    {
        return rtrim(rtrim($query), ',');
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

        return ($prefix ? $prefix . '_' : '') . $name . '.sql';
    }
}
