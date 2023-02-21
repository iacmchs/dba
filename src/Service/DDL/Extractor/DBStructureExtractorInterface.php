<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Model\DDL\TableStructure;

interface DBStructureExtractorInterface
{
    /**
     * Extract metadata from table by table name
     *
     * @return TableStructure[]
     */
    public function extractTables(): array;


    /**
     * Extract metadata from table by table name
     *
     * @param string $name
     * @return TableStructure
     */
    public function extractTable(string $name): TableStructure;
}
