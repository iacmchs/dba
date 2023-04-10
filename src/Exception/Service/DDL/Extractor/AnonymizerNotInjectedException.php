<?php

declare(strict_types=1);

namespace App\Exception\Service\DDL\Extractor;

class AnonymizerNotInjectedException extends \Exception
{
    public static function create(): self
    {
        return new self("Anonymizer not injected.");
    }
}
