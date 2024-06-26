<?php

/**
 * @file
 * Implementation of an exception that should be thrown if a  there is an attempt to get extractor for non-implemented
 * db driver.
 */

declare(strict_types=1);

namespace App\Exception\Service\Extractor;

use Exception;

class StructureExtractorNotFoundException extends Exception
{
    /**
     * Create exception instance with a prepared message.
     *
     * @param string $dbDriverName
     *
     * @return self
     */
    public static function byDbDriverName(string $dbDriverName): self
    {
        return new self(sprintf("There is no structure extractor with db driver '%s'.", $dbDriverName));
    }
}
