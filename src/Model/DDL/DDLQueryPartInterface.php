<?php

declare(strict_types=1);

namespace App\Model\DDL;

interface DDLQueryPartInterface
{
    /**
     * Create DDL query from class data
     *
     * @return string
     */
    public function toDDL(): string;
}
