<?php

declare(strict_types=1);

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
     * Returns entities config.
     *
     * @return array
     *   The database.entities section from project config.
     */
    public function getEntities(): array;

    /**
     * Returns anonymization config.
     *
     * @return array
     *   The database.anonymization section from project config.
     */
    public function getAnonymization(): array;

    /**
     * Returns processing options.
     *
     * @param string $sectionName
     *   The name of the section in options array.
     *
     * @return array
     *   The options.[section] from project config if $sectionName is set,
     *   the entire options array otherwise.
     */
    public function getOptions(string $sectionName = ''): array;

    /**
     * Returns table dump config.
     *
     * @param string $tableName
     *   DB table name.
     * @param bool $strict
     *   True - return config only if there is an item with table name as a key
     *   in the tables array. Otherwise, empty array is returned.
     *
     * @return array|float[]
     *   Table config from config file, enriched with default values for all
     *   available parameters.
     *   If there is no config for specified table then default config is
     *   returned anyway. Unless $strict=true - then empty array is returned.
     */
    public function getTableConfig(string $tableName, bool $strict = false): array;

    /**
     * Returns entity dump config.
     *
     * Entity is a table that has relationships with other tables.
     *
     * @param string $entityName
     *   Entity name.
     *
     * @return array|float[]
     *   Entity config from config file, enriched with default values for all
     *   available parameters.
     *   If there is no config for an entity then empty array is returned.
     */
    public function getEntityConfig(string $entityName): array;

    /**
     * Returns table anonymyzation config.
     *
     * @param string $tableName
     *   DB table name.
     *
     * @return array
     *   List of anonymization rules that can be used for specified table.
     */
    public function getTableAnonymization(string $tableName): array;

    /**
     * Returns the value of respective option.
     *
     * @param string $sectionName
     *   The name of the section in options array.
     * @param string $optionName
     *   The name of the option.
     *
     * @return mixed
     */
    public function getOption(string $sectionName, string $optionName): mixed;

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

    /**
     * Checks if some step/operation should be skipped during the export.
     *
     * @param string $sectionName
     *   The name of the section in options array.
     *
     * @return bool
     *   True if step/operation should be skipped, or false otherwise.
     */
    public function shouldSkip(string $sectionName): bool;
}
