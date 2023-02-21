<?php

declare(strict_types=1);

namespace App\Exception\Service\DDL;

use Exception;

class StructureExtractorNotFound extends Exception
{
    /**
     * @param string $dbDriverName
     * @return self
     */
    public static function byDBDriverName(string $dbDriverName): self
    {
        return new self(sprintf("There is no structure extractor with db driver %s", $dbDriverName));
    }
}
