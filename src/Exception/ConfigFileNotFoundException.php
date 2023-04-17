<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class ConfigFileNotFoundException extends Exception
{
    private const MESSAGE = "The configuration file '%s' not found or is empty.";

    public function __construct(string $filePath)
    {
        parent::__construct(sprintf(self::MESSAGE, $filePath));
    }
}
