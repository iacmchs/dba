<?php

declare(strict_types=1);

namespace App\Service\Extractor;

/**
 * Interface describe an db structure extractor.
 */
interface DbStructureExtractorInterface
{
    /**
     * Dump database to folder.
     *
     * @param string $path
     *
     * @return void
     */
    public function dumpStructure(string $path): void;
}
