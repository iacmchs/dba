<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Model\DDL\TableStructure;

interface DBStructureExtractorInterface
{
    /**
     * @return TableStructure[]
     */
    public function extractTables(): array;

    public function extractTable(string $name): TableStructure;
}
