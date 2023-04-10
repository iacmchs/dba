<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class UnknownAnonymizationMethodException extends Exception
{
    private const MESSAGE = "Unknown anonymization method '%s'.";

    public function __construct(string $method)
    {
        parent::__construct(sprintf(self::MESSAGE, $method));
    }
}
