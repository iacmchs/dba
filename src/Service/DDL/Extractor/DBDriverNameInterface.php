<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

interface DBDriverNameInterface
{
    public function getDBDriverName(): string;
}
