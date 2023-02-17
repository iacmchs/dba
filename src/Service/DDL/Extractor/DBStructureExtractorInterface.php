<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

interface DBStructureExtractorInterface
{
    public function extractTables();

    public function extractTable(string $name);
}
