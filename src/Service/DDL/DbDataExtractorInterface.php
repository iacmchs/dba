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
     * @param string      $table
     * @param string      $path
     * @param string|null $fileNamePrefix
     *
     * @return void
     */
    public function dumpTable(string $table, string $path, ?string $fileNamePrefix = ''): void;

    /**
     * Can table be dumped.
     *
     * @param string $table
     *
     * @return bool
     */
    public function canTableBeDumped(string $table): bool;
}
