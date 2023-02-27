<?php

/**
 * @file
 * DB connection setter.
 *
 * The interface should be implemented only for classes in which db connection cannot be injected through constructor.
 */

declare(strict_types=1);

namespace App\Service;

use PDO;

interface DbConnectionSetterInterface
{
    /**
     * Inject a db connection inside a object.
     *
     * @param PDO $connection
     * @return void
     */
    public function setDbConnection(PDO $connection): void;
}
