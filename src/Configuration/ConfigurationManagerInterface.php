<?php

namespace App\Configuration;

interface ConfigurationManagerInterface
{
    /**
     * Loads config from yml file.
     *
     * @param string $filePath
     *   Path to the config file.
     *
     * @return void
     */
    public function load(string $filePath): void;

    /**
     * Returns tables config.
     *
     * @return array
     *   The database.tables section from project config.
     */
    public function getTables(): array;

    /**
     * Returns table dump config.
     *
     * @param string $tableName
     *   DB table name.
     *
     * @return array|float[]
     *   Table config from config file, enriched with default values for all
     *   available parameters.
     */
    public function getTableConfig(string $tableName): array;

    /**
     * Get table data dump percentage.
     *
     * @param string $tableName
     *   DB table name.
     * @param array $tableConfig
     *   Table config. If not set then retrieved automatically by table name.
     *
     * @return float
     *   How much of table contents should be dumped - a value from 0 to 1,
     *   where 1 stands for 100%.
     *   If table is not listed in database.tables section of the config file
     *   then we assume that this table should be fully dumped (1 is returned).
     */
    public function getTablePercentage(string $tableName, array $tableConfig = []): float;

    /**
     * Checks if table can be dumped.
     *
     * @param string $tableName
     *   DB table name.
     * @param array $tableConfig
     *   Table config. If not set then retrieved automatically by table name.
     *
     * @return bool
     *   True if table data dump percentage is > 0.
     */
    public function canTableBeDumped(string $tableName, array $tableConfig = []): bool;
}
