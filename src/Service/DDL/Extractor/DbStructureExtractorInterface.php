<?php

/**
 * @file
 * Interface describe an db structure extractor.
 */

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Model\DDL\TableStructure;

interface DbStructureExtractorInterface
{
    /**
     * Extract all tables metadata.
     *
     * @return TableStructure[]
     */
    public function extractTables(): array;


    /**
     * Extract table metadata by table name.
     *
     * @param string $name
     * @return TableStructure
     */
    public function extractTable(string $name): TableStructure;

    /**
     * Dump database to folder.
     *
     * @return void
     */
    public function dumpStructure(): void;
}
