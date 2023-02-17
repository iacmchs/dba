<?php

declare(strict_types=1);

namespace App\Service;

interface DBConnectionSetterInterface
{
    public function setDBConnection(\PDO $connection): void;
}
