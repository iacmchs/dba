<?php

/**
 * @file Implementation of an exception that should be thrown if a db connect is not injected into the class.
 */

declare(strict_types=1);

namespace App\Exception\Service\DDL\Extractor;

use Exception;

class ConnectionNotInjected extends Exception
{
    /**
     * Create exception instance with a prepared message.
     *
     * @return self
     */
    public static function create(): self
    {
        return new self("DB connection not injected");
    }
}
