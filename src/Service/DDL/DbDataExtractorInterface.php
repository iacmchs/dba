<?php

namespace App\Service\DDL;

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
