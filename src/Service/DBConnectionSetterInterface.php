<?php

declare(strict_types=1);

namespace App\Service;

use PDO;

/**
 * DB connection setter.
 * The interface should be implemented only for classes in which db connection cannot be injected through constructor.
 */
interface DBConnectionSetterInterface
{
    /**
     * @param PDO $connection
     * @return void
     */
    public function setDBConnection(PDO $connection): void;
}
