<?php

/**
 * @file The interface shows that the object implementing it can be converted to a DDL query or to a part of a DDL query.
 */

declare(strict_types=1);

namespace App\Model\DDL;

interface DdlQueryPartInterface
{
    /**
     * Create DDL query from class data.
     *
     * @return string
     */
    public function toDDL(): string;
}
