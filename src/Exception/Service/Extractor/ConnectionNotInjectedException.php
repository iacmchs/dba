<?php

declare(strict_types=1);

namespace App\Exception\Service\Extractor;

class ConnectionNotInjectedException extends \Exception
{
    public static function create(): self
    {
        return new self("DB connection not injected.");
    }
}
