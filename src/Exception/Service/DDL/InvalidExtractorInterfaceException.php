<?php

declare(strict_types=1);

namespace App\Exception\Service\DDL;

use Exception;

/**
 * Implementation of an exception that should be thrown if a instance of extractor doesn't implement a necessary
 * interface.
 */
class InvalidExtractorInterfaceException extends Exception
{
    /**
     * Create exception instance with a prepared message.
     *
     * @param string $interface
     *
     * @return self
     */
    public static function byInterface(string $interface): self
    {
        return new self(sprintf("Extractor must implements a %s interface", $interface));
    }
}
