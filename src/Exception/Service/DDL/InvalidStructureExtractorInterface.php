<?php

declare(strict_types=1);

namespace App\Exception\Service\DDL;

use Exception;

class InvalidStructureExtractorInterface extends Exception
{
    public static function byInterface(string $interface): self
    {
        return new self(sprintf("Extractor must implements a %s interface", $interface));
    }
}
