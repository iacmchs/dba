<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;
use JetBrains\PhpStorm\Pure;

/**
 * @file Exception on unsuccessful dsn.
 */
class DsnNotValidException extends Exception
{
    private const MESSAGE = 'DSN %s is not valid';

    #[Pure]
    public function __construct(string $message)
    {
        parent::__construct(sprintf(self::MESSAGE, $message));
    }
}
