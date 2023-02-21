<?php

declare(strict_types=1);

namespace App\Model\DDL;

interface DDLQueryPartInterface
{
    public function toDDL(): string;
}
