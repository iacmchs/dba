<?php

namespace App\Service\Extractor;

/**
 * DB data extractor.
 */
interface DbDataExtractorInterface
{
    /**
     * Dump table.
     *
     * @param string $tableName
     *   DB table name.
     * @param string $dir
     *   Path do directory that contains exported files.
     * @param array $tableConfig
     *   Table config. If not set then retrieved automatically by table name.
     * @param string $fileNamePrefix
     *   File name prefix.
     *
     * @return void
     */
    public function dumpTable(string $tableName, string $dir, array $tableConfig = [], string $fileNamePrefix = ''): void;

    /**
     * Dump entity.
     *
     * Entity is a table that has relationships with other tables.
     *
     * @param string $entityName
     *   Entity name.
     * @param string $dir
     *   Path do directory that contains exported files.
     * @param array $entityConfig
     *   Entity config. If not set then retrieved automatically by entity name.
     * @param string $fileNamePrefix
     *   File name prefix.
     *
     * @return void
     */
    public function dumpEntity(string $entityName, string $dir, array $entityConfig = [], string $fileNamePrefix = ''): void;

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
