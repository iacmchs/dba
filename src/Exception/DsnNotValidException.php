<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;
use JetBrains\PhpStorm\Pure;

class DsnNotValidException extends Exception
{
    private const MESSAGE = "DSN '%s' is not valid.";

    #[Pure]
    public function __construct(string $dsn)
    {
        parent::__construct(sprintf(self::MESSAGE, $dsn));
    }
}
