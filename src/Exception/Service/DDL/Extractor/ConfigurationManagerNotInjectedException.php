<?php

declare(strict_types=1);

namespace App\Exception\Service\DDL\Extractor;

class ConfigurationManagerNotInjectedException extends \Exception
{
    public static function create(): self
    {
        return new self("Configuration manager not injected.");
    }
}
