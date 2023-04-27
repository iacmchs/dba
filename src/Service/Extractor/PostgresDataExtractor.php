<?php

declare(strict_types=1);

namespace App\Service\Extractor;

use App\Configuration\ConfigurationManagerInterface;
use App\Exception\Service\Extractor\AnonymizerNotInjectedException;
use App\Exception\Service\Extractor\ConfigurationManagerNotInjectedException;
use App\Exception\Service\Extractor\ConnectionNotInjectedException;
use App\Service\Anonymization\AnonymizerInterface;
use App\Service\Anonymization\AnonymizerSetterInterface;
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
    AnonymizerSetterInterface,
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

    /**
     * Data anonymizer.
     *
     * @var \App\Service\Anonymization\AnonymizerInterface|null
     */
    private ?AnonymizerInterface $anonymizer;

    /**
     * Configuration manager.
     *
     * @var \App\Configuration\ConfigurationManagerInterface|null
     */
    private ?ConfigurationManagerInterface $configurationManager;

    /**
     * Data for internal usage.
     *
     * @var array
     */
    private array $data = [];

    /**
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
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

        $this->dumpBase($dir, $tableConfig, [], $fileNamePrefix);
    }

    /**
     * @inheritDoc
     */
    public function dumpEntity(string $entityName, string $dir, array $entityConfig = [], string $fileNamePrefix = '20'): void
    {
        if (!$entityConfig) {
            $entityConfig = $this->getConfigurationManager()->getEntityConfig($entityName);
        }

        $params = [
            'check_ids' => true,
        ];
        $this->dumpBase($dir, $entityConfig, $params, $fileNamePrefix);
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
    public function setAnonymizer(AnonymizerInterface $anonymizer): void
    {
        $this->anonymizer = $anonymizer;
    }

    /**
     * @inheritDoc
     */
    public function setConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Base method to dump table/entity.
     *
     * @param string $dir
     *   Path do directory that contains exported files.
     * @param array $config
     *   Table/entity config.
     * @param array $params
     *   Processing params. The following are now available:
     *   - check_ids: TRUE - check row id before dumping to avoid duplicates.
     * @param string $fileNamePrefix
     *   File name prefix.
     *
     * @return void
     *
     * @throws \App\Exception\Service\Extractor\AnonymizerNotInjectedException
     * @throws \App\Exception\Service\Extractor\ConfigurationManagerNotInjectedException
     * @throws \App\Exception\Service\Extractor\ConnectionNotInjectedException
     * @throws \Doctrine\DBAL\Exception
     */
    private function dumpBase(string $dir, array $config, array $params = [], string $fileNamePrefix = ''): void
    {
        if (!$config) {
            return;
        }

        // Prepare some data.
        $tableAnonymization = $this->getConfigurationManager()->getTableAnonymization($config['table']);
        $filePath = $dir . '/' . $this->getNewTableFileName($config['table'], $fileNamePrefix);
        $sql = $insertSql = "INSERT INTO {$config['table']} VALUES" . PHP_EOL;

        $needSaveAfterEachRow = ($config['export_method'] ?? '') === 'row';
        $hasResult = false;
        $i = 0;
        $query = $this->getDataSelectQuery($config);

        if (!$query) {
            return;
        }

        // Get rows from db table and export them.
        foreach ($this->getConnection()->iterateAssociative($query) as $row) {
            $i++;
            $hasResult = true;

            // If we need to check ids to avoid duplicate rows.
            if (!empty($params['check_ids']) && !empty($config['fields']['id'])) {
                $rowId = (string) ($row[$config['fields']['id']] ?? '');
                if ($this->getRowIdFromStorage($config['table'], $rowId)) {
                    continue;
                }

                $this->saveRowIdToStorage($config['table'], $rowId);
            }

            // Export previous row to file.
            if ($needSaveAfterEachRow && $sql !== $insertSql) {
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
            $row = $this->getAnonymizer()->anonymize($config['table'], $row, $tableAnonymization);
            $sql .= $this->getValuesQuery($row) . ',' . PHP_EOL;

            // Export relations (if any).
            $this->dumpTableRelations($config['relations'] ?? [], $row, $dir, $params, $fileNamePrefix);
        }

        // Export last row (or all rows) to file.
        if ($hasResult && $sql !== $insertSql) {
            $sql = $this->removeTrailingComma($sql) . ';' . PHP_EOL;
            $this->filesystem->appendToFile($filePath, $sql);
        }
    }

    /**
     * Dump data from related tables.
     *
     * @param array $relations
     *   Related tables' config.
     * @param array $row
     *   A row from current table.
     * @param string $dir
     *   Path do directory that contains exported files.
     * @param array $params
     *   Processing params. The following are now available:
     *   - check_ids: TRUE - check row id to avoid duplicate rows.
     * @param string $fileNamePrefix
     *   File name prefix.
     *
     * @return void
     *
     * @throws \App\Exception\Service\Extractor\AnonymizerNotInjectedException
     * @throws \App\Exception\Service\Extractor\ConfigurationManagerNotInjectedException
     * @throws \App\Exception\Service\Extractor\ConnectionNotInjectedException
     * @throws \Doctrine\DBAL\Exception
     */
    private function dumpTableRelations(array $relations, array $row, string $dir, array $params = [], string $fileNamePrefix = ''): void
    {
        foreach ($relations as $relationName => $relationConfig) {
            $relationConfig += [
                'table' => $relationName,
                'get' => 1,
            ];

            // If relation is an entity.
            if (!empty($relationConfig['is_entity'])) {
                // Get entity type, bundle and id.
                $entityType = str_starts_with($relationConfig['values']['type'] ?? '', '%')
                    ? $row[substr($relationConfig['values']['type'], 1)]
                    : $relationConfig['values']['type'] ?? '';
                $entityBundle = str_starts_with($relationConfig['values']['bundle'] ?? '', '%')
                    ? $row[substr($relationConfig['values']['bundle'], 1)]
                    : $relationConfig['values']['bundle'] ?? '';
                $entityId = str_starts_with((string) ($relationConfig['values']['id'] ?? ''), '%')
                    ? $row[substr($relationConfig['values']['id'], 1)]
                    : $relationConfig['values']['id'] ?? '';

                // If type/bundle are not defined then we take it from db table
                // that represents current entity. Let's just find entity by id.
                if (!$entityType && !empty($relationConfig['fields']['type'])
                    || !$entityBundle && $relationConfig['fields']['bundle']
                ) {
                    $query = [
                        'table' => $relationConfig['table'],
                        'get' => 1,
                        'where' => [
                            $relationConfig['fields']['id'] => $entityId,
                        ],
                        'limit' => 1,
                    ];

                    // Get entity type and bundle.
                    $query = $this->getDataSelectQuery($query);
                    foreach ($this->getConnection()->iterateAssociative($query) as $entityRow) {
                        if (!$entityType) {
                            $entityType = $entityRow[$relationConfig['fields']['type']];
                        }
                        if (!$entityBundle) {
                            $entityBundle = $entityRow[$relationConfig['fields']['bundle']];
                        }
                    }
                }

                $config = $this->getConfigurationManager()->getEntityConfig($entityType . ($entityBundle ? '__' . $entityBundle : ''));
                // If related entity type should be fully dumped then we
                // don't need to dump any particular entity here.
                if (($config['get'] ?? 0) === 1) {
                    continue;
                }

                // Otherwise we should dump a current (single) entity.
                if ($config) {
                    $config['where'][$relationConfig['fields']['id']] = $entityId;
                    $config['get'] = 1;
                }

            // If relation is a table.
            } else {
                $config = $this->getConfigurationManager()->getTableConfig($relationConfig['table']);
                // If related table should be fully dumped then we
                // don't need to dump any particular row here.
                if (($config['get'] ?? 0) === 1) {
                    continue;
                }

                // Preprocess conditions.
                foreach ($relationConfig['where'] ?? [] as $key => $value) {
                    // If value is like `%fieldname` then we need to copy a
                    // value of respective field from parent table row.
                    if (str_starts_with((string) $value, '%')) {
                        $relationConfig['where'][$key] = $row[substr($value, 1)];
                    }
                }

                $config = $relationConfig;
            }

            $this->dumpBase($dir, $config, $params, $fileNamePrefix);
        }
    }

    /**
     * Saves row id to internal storage.
     *
     * This may be used to perform and additional check to save unique rows only
     * and avoid duplicates in export files.
     *
     * @param string $tableName
     *   DB table name.
     * @param string $id
     *   Row id.
     *
     * @return void
     */
    private function saveRowIdToStorage(string $tableName, string $id): void
    {
        $this->data['tables'][$tableName][$id] = $id;
    }

    /**
     * Retrieves row id from internal storage.
     *
     * This may be used to perform and additional check to save unique rows only
     * and avoid duplicates in export files.
     *
     * @param string $tableName
     *   DB table name.
     * @param string $id
     *   Row id.
     *
     * @return string
     *   Row id if it's already exists in storage, or empty string otherwise.
     */
    private function getRowIdFromStorage(string $tableName, string $id): string
    {
        return $this->data['tables'][$tableName][$id] ?? '';
    }

    /**
     * Returns select query to get data from table.
     *
     * @param array $tableConfig
     *   Table config.
     *
     * @return string
     *   The SELECT sql query to get data from table.
     *   If $tableConfig['get'] === 0 (should select nothing) then empty string
     *   is returned.
     */
    private function getDataSelectQuery(array $tableConfig): string
    {
        if (empty($tableConfig['get'])) {
            return '';
        }

        $query = 'SELECT * FROM ' . $tableConfig['table'];

        $where = [];
        foreach ($tableConfig['where'] ?? [] as $fieldName => $condition) {
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
            $query .= ' WHERE (' . implode(') AND (', $where) . ')';
        }

        if (!empty($tableConfig['limit'])) {
            $query .= ' LIMIT ' . $tableConfig['limit'];
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
            if (is_null($item)) {
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
     * Returns configuration manager.
     *
     * @return \App\Service\Anonymization\AnonymizerInterface
     *
     * @throws \App\Exception\Service\Extractor\AnonymizerNotInjectedException
     */
    private function getAnonymizer(): AnonymizerInterface
    {
        if (!$this->anonymizer) {
            throw AnonymizerNotInjectedException::create();
        }

        return $this->anonymizer;
    }
}
