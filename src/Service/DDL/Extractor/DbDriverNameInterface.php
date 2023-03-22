<?php

/**
 * @file
 * Interface guarantees that class implementing it will return a name of a db driver.
 */

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

interface DbDriverNameInterface
{
    /**
     * Gets name of the db driver that an instance uses to connect to db.
     *
     * @return string
     */
    public function getDbDriver(): string;
}
