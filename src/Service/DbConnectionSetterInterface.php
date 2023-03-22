<?php

/**
 * @file
 * DB connection setter.
 *
 * The interface should be implemented only for classes in which db connection cannot be injected through constructor.
 */

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;

interface DbConnectionSetterInterface
{
    /**
     * Inject a db connection inside a object.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function setDbConnection(Connection $connection): void;
}
