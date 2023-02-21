<?php

declare(strict_types=1);

namespace App\Exception\Service\DDL\Extractor;

use Exception;

class ConnectionNotInjected extends Exception
{
    /**
     * @return self
     */
    public static function create(): self
    {
        return new self("DB connection not injected");
    }
}
